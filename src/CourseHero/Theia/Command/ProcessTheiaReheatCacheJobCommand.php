<?php

namespace CourseHero\TheiaBundle\Command;

use CourseHero\AdminBundle\Entity\UrlRemoval;
use CourseHero\AdminBundle\Entity\UrlRemovalBatch;
use CourseHero\AdminBundle\Entity\UrlRemovalBatchRepository;
use CourseHero\AdminBundle\Entity\UrlRemovalRequest;
use CourseHero\AdminBundle\Entity\UrlRemovalRequestRepository;
use CourseHero\AdminBundle\Service\DmcaService;
use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\QueueBundle\Component\QueueMessageInterface;
use CourseHero\QueueBundle\Constant\Queue;
use CourseHero\QueueBundle\Service\QueueService;
use CourseHero\UtilsBundle\Command\AbstractPerpetualCommand;
use CourseHero\UtilsBundle\Service\SlackMessengerService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class TheiaJobHandler
{
    protected $componentLibrary;
    protected $queue;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    protected function createRenderJob(string $component, string $props)
    {
        $message = $this->queue->createMessage($props);
        $message->setAttributes([
            'Type' => [
                'DataType' => 'String',
                'StringValue' => 'render-job'
            ],
            'ComponentLibrary' => [
                'DataType' => 'String',
                'StringValue' => $this->componentLibrary
            ],
            'Component' => [
                'DataType' => 'String',
                'StringValue' => $component
            ]
        ]);
        $this->queue->sendMessage($message);
    }

    protected function createProducerJob(string $producerGroup)
    {
        $message = $this->queue->createMessage([
            'producerGroup' => $producerGroup
        ]);
        $message->setAttributes([
            'Type' => [
                'DataType' => 'String',
                'StringValue' => 'producer-job'
            ],
            'ComponentLibrary' => [
                'DataType' => 'String',
                'StringValue' => $this->componentLibrary
            ]
        ]);
        $this->queue->sendMessage($message);
    }

    abstract public function processNewBuildJob(string $builtAt, string $commitHash);
    abstract public function processProducerJob(string $producerGroup);
}

class StudyGuideTheiaJobHandler extends TheiaJobHandler
{
    public function __construct(QueueInterface $queue)
    {
        parent::__construct($queue);
        $this->componentLibrary = '@coursehero-components/study-guides';
    }

    public function processNewBuildJob(string $builtAt, string $commitHash)
    {
        // get all published course names

        // make producer job for each one
        $this->createProducerJob('Biology');
        $this->createProducerJob('Biology 2');
        $this->createProducerJob('Biology 3');
    }

    public function processProducerJob(string $producerGroup)
    {
        $this->createRenderJob('IndexApp', '{}');
    }
}

/**
 * Class ProcessTheiaReheatCacheJobCommand
 * @package CourseHero\TheiaBundle\Command
 *
 * Command used to process the UrlRemoval queue
 */
class ProcessTheiaReheatCacheJobCommand extends AbstractPerpetualCommand
{
    const CMD_NAME = 'ch:theia:job';

    // Number of times to try to find a new SQS item and get started on that
    // Does not apply if no SQS item could be found at all
    const RETRY_TIMES = 5;

    /** @var  QueueInterface */
    protected $queue;

    protected function configure()
    {
        $this->setName(self::CMD_NAME)
            ->setDescription('Processes a Theia cache reheat job');
    }

    public function singleRun(InputInterface $input, OutputInterface $output)
    {
        // $this->write('Starting to process next Theia job');
        $queue = $this->getQueue();

        $this->handlers = [
            '@coursehero-components/study-guides' => new StudyGuideTheiaJobHandler($queue)
        ];
        
        try {
            $message = $this->getQueueItem();
            if (!$message) {
                return; // if no more messages just return and restart for now
            }

            $this->write("Job processing started");
            $job = $this->processJob($message);
            $this->write("Finished job");
            // $queue->deleteMessage($message);
        } catch (\Exception $e) {
            // if no message we encountered an error getting a message
            if (!isset($message)) {
                // TODO ?
                // $this->getSlackMessengerService()->send("Exception: {$e->getMessage()}", '#csi-debug', 'dmca-bot');
                return;
            }

            // if we didn't successfully process the message, requeue and release 'lock' on request
            // $queue->createMessage($message->getBody());
            // $this->getSlackMessengerService()->send("Exception processing {$message->getBody()}: {$e->getMessage()}", '#csi-debug', 'dmca-bot');
        }
    }

    /**
     * Retrieves the next SQS item
     * Returns a message or null if one could not be found
     */
    protected function getQueueItem()
    {
        $queueName = $this->getQueueName();
        $urlRemovalQueue = $this->getQueueService()->getQueue($queueName);
        $this->write('Finding next queue item', OutputInterface::VERBOSITY_VERBOSE);
        // if nothing left in the queue just return null, no need to retry
        $message = $urlRemovalQueue->receiveMessage();
        if (!$message) {
            return null;
        }

        return $message;
    }

    protected function processJob(QueueMessageInterface $message)
    {
        $body = $message->getBody();
        $attrs = $message->getAttributes();
        $componentLibrary = $attrs['ComponentLibrary']['StringValue'];
        $jobHandler = $this->handlers[$componentLibrary];
        $type = $attrs['Type']['StringValue'];

        $this->write("Processing $type");

        if ($type === 'render-job') {
            $props = json_encode($body); // TODO $message->body is already deserialized into an object - this is a waste of processing for our use case.
            $this->processRenderJob($componentLibrary, $attrs['Component']['StringValue'], $props);
        } else if ($type === 'producer-job') {
            $jobHandler->processProducerJob($body['producerGroup']);
        } else if ($type === 'new-build-job') {
            $jobHandler->processNewBuildJob($body['builtAt'], $body['commitHash']);
        } else {
            throw new \Exception("unexpected job type: $type");
        }
    }

    protected function processRenderJob(string $componentLibrary, string $component, string $propsAsJson)
    {
        $this->write("got a job ... \n");
        var_dump($job);

        // TODO make theia request here
    }

    protected function getQueue()
    {
        if ($this->queue) {
            return $this->queue;
        }
        $queueName = $this->getQueueName();
        $this->queue = $this->getQueueService()->getQueue($queueName);
        return $this->queue;
    }

    protected function getQueueName(): string
    {
        $environment = $this->getContainer()->getParameter('environment');
        return 'production' === $environment ? Queue::THEIA_REHEAT_JOBS : Queue::THEIA_REHEAT_JOBS_DEV;
    }

    protected function getQueueService(): QueueService
    {
        return $this->getContainer()->get(QueueService::SERVICE_ID);
    }

    protected function getSlackMessengerService(): SlackMessengerService
    {
        return $this->getContainer()->get(SlackMessengerService::SERVICE_ID);
    }

    protected function getEm(): EntityManager
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
