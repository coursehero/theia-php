<?php

namespace CourseHero\TheiaBundle\Command;

use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\QueueBundle\Component\QueueMessageInterface;
use CourseHero\QueueBundle\Constant\Queue;
use CourseHero\QueueBundle\Service\QueueService;
use CourseHero\StudyGuideBundle\Constants\StageConstants;
use CourseHero\StudyGuideBundle\Service\StudyGuideConnectionService;
use CourseHero\TheiaBundle\Service\TheiaProviderService;
use CourseHero\UtilsBundle\Command\AbstractPerpetualCommand;
use CourseHero\UtilsBundle\Service\SlackMessengerService;
use JMS\Serializer\SerializerBuilder;
use JMS\SerializerBundle\JMSSerializerBundle;
use StudyGuideBlocks\Blocks\Block;
use StudyGuideBlocks\Blocks\CourseBlock;
use StudyGuideBlocks\Blocks\SectionBlock;
use StudyGuideBlocks\Blocks\SubtopicBlock;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class TheiaJobHandler
{
    /** @var string */
    public static $componentLibrary;

    /** @var QueueInterface */
    protected $queue;

    /** @var \Theia\Client */
    protected $theiaClient;

    public function __construct(QueueInterface $queue, \Theia\Client $theiaClient)
    {
        $this->queue = $queue;
        $this->theiaClient = $theiaClient;
    }

    protected function createRenderJob(string $component, string $props)
    {
        $message = $this->queue->createMessage($props);
        $message->setAttributes(
            [
                'Type' => [
                    'DataType' => 'String',
                    'StringValue' => 'render-job',
                ],
                'ComponentLibrary' => [
                    'DataType' => 'String',
                    'StringValue' => self::$componentLibrary,
                ],
                'Component' => [
                    'DataType' => 'String',
                    'StringValue' => $component,
                ],
            ]
        );
        $this->queue->sendMessage($message);
    }

    protected function createProducerJob(string $producerGroup)
    {
        $message = $this->queue->createMessage(['producerGroup' => $producerGroup]);
        $message->setAttributes(
            [
                'Type' => [
                    'DataType' => 'String',
                    'StringValue' => 'producer-job',
                ],
                'ComponentLibrary' => [
                    'DataType' => 'String',
                    'StringValue' => self::$componentLibrary,
                ],
            ]
        );
        $this->queue->sendMessage($message);
    }

    abstract public function processNewBuildJob(string $builtAt, string $commitHash);

    abstract public function processProducerJob(string $producerGroup);
}

class StudyGuideTheiaJobHandler extends TheiaJobHandler
{
    /** @var StudyGuideConnectionService */
    private $studyGuideConnectionService;

    /** @var JMSSerializerBundle */
    private $jmsSerializer;

    public function __construct(QueueInterface $queue, \Theia\Client $theiaClient, StudyGuideConnectionService $studyGuideConnectionService)
    {
        parent::__construct($queue, $theiaClient);
        $this->studyGuideConnectionService = $studyGuideConnectionService;
        $this->jmsSerializer = SerializerBuilder::create()->build();
        self::$componentLibrary = '@coursehero-components/study-guides';
    }

    /**
     * @param string $builtAt
     * @param string $commitHash
     * @throws \Exception
     */
    public function processNewBuildJob(string $builtAt, string $commitHash)
    {
        $courseBlocks = $this->studyGuideConnectionService->getCoursesTree(StageConstants::STAGE_PUBLISHED);
        $this->createRenderJob('IndexApp', $this->jmsSerializer->serialize(['courses' => $courseBlocks], 'json'));

        /** @var CourseBlock $courseBlock */
        foreach ($courseBlocks as $courseBlock) {
            $this->createProducerJob($courseBlock->getName());
        }
    }

    /**
     * @param string $producerGroup
     * @throws \Exception
     */
    public function processProducerJob(string $producerGroup)
    {
        $courseTree = $this->studyGuideConnectionService->getCourseTree($producerGroup, StageConstants::STAGE_PUBLISHED);
        /** @var Block $block */
        foreach ($courseTree->createIterator() as $block) {
            if (in_array($block->getBlockType(), [SectionBlock::BLOCK_TYPE, SubtopicBlock::BLOCK_TYPE])) {
                $props = $this->jmsSerializer->serialize(["course" => $courseTree, "location" => $this->route($block)], 'json');
                $this->theiaClient->renderAndCache(self::$componentLibrary, 'CourseApp', $props, true);
            }
        }
    }

    /**
     * Needs to match implementation in components/study-guides/src/utils/route.js
     *
     * @param Block $block
     * @return string
     */
    private function route(Block $block): string
    {
        $slug = function (string $string): string {
            $removePatterns = [
                '\?',
                // '\(',
                // '\)',
                ','
            ];
            $removePatternsRegex = '/' . join('|', $removePatterns) . '/';
            $spacesReplaced = preg_replace('/\s/', '-', $string);
            $charsRemoved = preg_replace($removePatternsRegex, '', $spacesReplaced);
            return strtolower($charsRemoved);
        };

        $createUrl = function (string $courseSlug, string $subtopicSlug, string $sectionSlug): string {
            if ($sectionSlug === 'overview') {
                $sectionSlug = '';
            }
            $parts = array_filter(['study-guides', $courseSlug, $subtopicSlug, $sectionSlug]);
            return '/' . join('/', $parts) . '/';
        };

        $blockLevels = [$block];
        while ($block->getParent()) {
            $block = $block->getParent();
            array_unshift($blockLevels, $block);
        }

        $courseSlug = $slug($blockLevels[0]->getName());
        $subtopicSlug = isset($blockLevels[2]) ? $slug($blockLevels[2]->getName()) : '';
        $sectionSlug = isset($blockLevels[3]) ? $slug($blockLevels[3]->getName()) : '';

        return $createUrl($courseSlug, $subtopicSlug, $sectionSlug);
    }
}

/**
 * Class ProcessTheiaReheatCacheJobCommand
 * @package CourseHero\TheiaBundle\Command
 *
 * Command used to process the Theia cache reheat jobs
 */
class ProcessTheiaReheatCacheJobCommand extends AbstractPerpetualCommand
{
    const CMD_NAME = 'ch:theia:job';

    /** @var  QueueInterface */
    protected $queue;

    /** @var \Theia\Client */
    protected $theiaClient;

    /** @var array */
    protected $handlers;

    protected function configure()
    {
        $this->setName(self::CMD_NAME)
            ->setDescription('Processes a Theia cache reheat job');
    }

    public function singleRun(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getQueue();
        $theiaClient = $this->getTheiaClient();
        $studyGuideConnectionService = $this->getStudyGuideConnectionService();

        $this->handlers = [
            StudyGuideTheiaJobHandler::$componentLibrary => new StudyGuideTheiaJobHandler($queue, $theiaClient, $studyGuideConnectionService),
        ];

        try {
            $message = $this->getQueueItem();
            if (!$message) {
                return; // if no more messages just return and restart for now
            }
            // Delete message from queue so long jobs don't get processed multiple times
            $queue->deleteMessage($message);

            $this->write("Job processing started");
            $this->processJob($message);
            $this->write("Finished job\n");
        } catch (\Exception $e) {
            if (isset($message)) {
                $queue->sendMessage($message);
            }
            $this->write("Job failed {$e->getMessage()}");
            $this->sendSlackMessage("Exception: {$e->getMessage()}");
        }
    }

    /**
     * Retrieves the next SQS item
     * Returns a message or null if one could not be found
     * @return QueueMessageInterface|null
     */
    protected function getQueueItem()
    {
        $queue = $this->getQueue();
        $this->write('Finding next queue item', OutputInterface::VERBOSITY_VERBOSE);

        // if nothing left in the queue just return null, no need to retry
        return $queue->receiveMessage() ?: null;
    }

    /**
     * @param QueueMessageInterface $message
     * @throws \Exception
     */
    protected function processJob(QueueMessageInterface $message)
    {
        $body = $message->getBody();
        $attrs = $message->getAttributes();
        $componentLibrary = $attrs['ComponentLibrary']['StringValue'];
        $jobHandler = $this->handlers[$componentLibrary];
        $type = $attrs['Type']['StringValue'];

        $attrsAsString = json_encode($attrs, JSON_PRETTY_PRINT);
        $this->write("Processing $type with $attrsAsString");
        if ($type !== 'render-job') {
            $bodyAsString = json_encode($body, JSON_PRETTY_PRINT);
            $this->write("Body: $bodyAsString");
        }

        if ($type === 'new-build-job') {
            $jobHandler->processNewBuildJob($body['builtAt'], $body['commitHash']);
        } elseif ($type === 'producer-job') {
            $jobHandler->processProducerJob($body['producerGroup'], $body);
        } elseif ($type === 'render-job') {
            $props = json_encode($body); // TODO $message->body is already deserialized into an object - this is a waste of processing for our use case.
            $component = $attrs['Component']['StringValue'];
            $this->processRenderJob($componentLibrary, $component, $props);
        } else {
            throw new \Exception("unexpected job type: $type");
        }
    }

    protected function processRenderJob(string $componentLibrary, string $component, string $props)
    {
        $this->getTheiaClient()->renderAndCache($componentLibrary, $component, $props, true);
    }

    protected function getQueue(): QueueInterface
    {
        if (!$this->queue) {
            $queueName = $this->getQueueName();
            $this->queue = $this->getQueueService()->getQueue($queueName);
        }

        return $this->queue;
    }

    protected function getQueueName(): string
    {
        $environment = $this->getContainer()->getParameter('environment');

        return 'production' === $environment ? Queue::THEIA_REHEAT_JOBS : Queue::THEIA_REHEAT_JOBS_DEV;
    }

    protected function getTheiaClient(): \Theia\Client
    {
        if (!$this->theiaClient) {
            $this->theiaClient = $this->getTheiaProviderService()->getClient();
        }

        return $this->theiaClient;
    }

    protected function sendSlackMessage(string $text)
    {
        if ($this->getContainer()->getParameter('environment') !== 'localhost') {
            $this->getSlackMessengerService()->send($text, $this->getSlackChannelName(), 'IRIS', ':eye:');
        }
    }

    protected function getSlackChannelName(): string
    {
        $environment = $this->getContainer()->getParameter('environment');

        return 'production' === $environment ? '#theia-errors-prod' : '#theia-errors-dev';
    }

    protected function getQueueService(): QueueService
    {
        return $this->getContainer()->get(QueueService::SERVICE_ID);
    }

    protected function getStudyGuideConnectionService(): StudyGuideConnectionService
    {
        return $this->getContainer()->get(StudyGuideConnectionService::SERVICE_ID);
    }

    protected function getTheiaProviderService(): TheiaProviderService
    {
        return $this->getContainer()->get(TheiaProviderService::SERVICE_ID);
    }

    protected function getSlackMessengerService(): SlackMessengerService
    {
        return $this->getContainer()->get(SlackMessengerService::SERVICE_ID);
    }
}
