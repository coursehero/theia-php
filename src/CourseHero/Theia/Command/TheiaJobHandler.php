<?php

namespace CourseHero\TheiaBundle\Command;

use CourseHero\QueueBundle\Component\QueueInterface;

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
