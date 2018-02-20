<?php

namespace CourseHero\TheiaBundle\Command;

use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\QueueBundle\Component\QueueMessageInterface;
use CourseHero\QueueBundle\Constant\Queue;
use CourseHero\QueueBundle\Service\QueueService;
use CourseHero\StudyGuideBundle\Service\StudyGuideConnectionService;
use CourseHero\TheiaBundle\Service\TheiaProviderService;
use CourseHero\UtilsBundle\Command\AbstractPerpetualCommand;
use CourseHero\UtilsBundle\Service\SlackMessengerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
