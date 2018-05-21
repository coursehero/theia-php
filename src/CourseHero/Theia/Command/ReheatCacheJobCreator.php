<?php

namespace CourseHero\TheiaBundle\Command;

use CourseHero\QueueBundle\Component\QueueInterface;

class ReheatCacheJobCreator
{
    /** @var QueueInterface */
    protected $queue;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @param string $componentLibrary
     * @param string $producerGroup
     * @param array $jobParams
     * @throws \Exception
     */
    public function createProducerJob(string $componentLibrary, string $producerGroup, array $jobParams = [])
    {
        $message = $this->queue->createMessage([
            'producerGroup' => $producerGroup,
            'jobParams' => $jobParams,
        ]);
        $message->setAttributes(
            [
                'Type' => [
                    'DataType' => 'String',
                    'StringValue' => 'producer-job',
                ],
                'ComponentLibrary' => [
                    'DataType' => 'String',
                    'StringValue' => $componentLibrary,
                ],
            ]
        );

        $success = $this->queue->sendMessage($message);
        if (!$success) {
            throw new \Exception("Failed to send producer-job to queue");
        }
    }

    /**
     * @param string $componentLibrary
     * @param string $component
     * @param string $props
     * @throws \Exception
     */
    public function createRenderJob(string $componentLibrary, string $component, string $props)
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
                    'StringValue' => $componentLibrary,
                ],
                'Component' => [
                    'DataType' => 'String',
                    'StringValue' => $component,
                ],
            ]
        );

        $success = $this->queue->sendMessage($message);
        if (!$success) {
            throw new \Exception("Failed to send render-job to queue");
        }
    }
}
