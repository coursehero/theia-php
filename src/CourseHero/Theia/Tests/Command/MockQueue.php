<?php

namespace CourseHero\TheiaBundle\Tests\Command;

use CourseHero\QueueBundle\Component\AmazonQueueMessage;
use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\QueueBundle\Component\QueueMessageInterface;

class MockQueue implements QueueInterface
{
    /**
     * @var QueueMessageInterface[]
     */
    private $messages = [];

    public function init(array $params)
    {
    }

    public function getName()
    {
        return 'MockQueue';
    }

    public function createMessage($body = '')
    {
        $message = new AmazonQueueMessage();
        $message->setBody($body);
        return $message;
    }

    public function sendMessage(QueueMessageInterface $message): bool
    {
        array_push($this->messages, $message);
        return true;
    }

    public function sendMessageBatch(array $messages): array
    {
        $this->messages = array_merge($this->messages, $messages);
        return array_fill(0, count($messages), true);
    }

    public function receiveMessage($noWait = false)
    {
        return array_shift($this->messages);
    }

    public function receiveMessages($maxMessages = 10, $noWait = false)
    {
        $messages = array_slice($this->messages, 0, $maxMessages);
        $this->messages = array_slice($this->messages, $maxMessages);
        return $messages;
    }

    public function deleteMessage(QueueMessageInterface $message)
    {
        $this->messages = array_filter($this->messages, function (QueueMessageInterface $m) use ($message) {
            return $m->getId() != $message->getId();
        });
    }

    public function setFallbackQueues(array $queues)
    {
    }

    public function useFallback(QueueMessageInterface $message, $errorMessage)
    {
    }

    public function numMessagesInQueue()
    {
        return count($this->messages);
    }
}
