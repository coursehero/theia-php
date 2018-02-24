<?php

namespace CourseHero\TheiaBundle\Tests\Command;

use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\TheiaBundle\Command\ReheatCacheJobCreator;

class ReheatCacheJobCreatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ReheatCacheJobCreator */
    protected $jobCreator;

    /** @var QueueInterface */
    protected $queue;

    public function setUp()
    {
        $this->queue = new MockQueue();
        $this->jobCreator = new ReheatCacheJobCreator($this->queue);
    }

    public function testCreateProducerJob()
    {
        $this->jobCreator->createProducerJob('componentLibrary', 'producerGroup');
        $msg = $this->queue->receiveMessage();
        $this->assertSame('producer-job', $msg->getAttributes()['Type']['StringValue']);
        $this->assertSame('componentLibrary', $msg->getAttributes()['ComponentLibrary']['StringValue']);
        $this->assertSame('producerGroup', $msg->getBody()['producerGroup']);
    }

    public function testCreateRenderJob()
    {
        $this->jobCreator->createRenderJob('componentLibrary', 'component', 'props');
        $msg = $this->queue->receiveMessage();
        $this->assertSame('render-job', $msg->getAttributes()['Type']['StringValue']);
        $this->assertSame('componentLibrary', $msg->getAttributes()['ComponentLibrary']['StringValue']);
        $this->assertSame('component', $msg->getAttributes()['Component']['StringValue']);
        $this->assertSame('props', $msg->getBody());
    }
}
