<?php

namespace CourseHero\TheiaBundle\Controller;

use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\TheiaBundle\Tests\MockQueue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ReheatCacheControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $reheatCacheController;

    /** @var QueueInterface */
    protected $queue;

    public function setUp()
    {
        $this->queue = new MockQueue();
        $this->reheatCacheController = $this->getMockBuilder(ReheatCacheController::class)
            ->disableOriginalConstructor()
            ->setMethods(['requireRight', 'getQueue'])
            ->getMock();

        $this->reheatCacheController->method('requireRight')->willReturn(null);
        $this->reheatCacheController->method('getQueue')->willReturn($this->queue);
    }

    public function testReheatCache_sendsProducerJobToQueue()
    {
        $this->reheatCacheController->reheatCache($this->getSampleRequest('the-component-library', 'Group A'));
        $msg = $this->queue->receiveMessage();
        $this->assertSame('producer-job', $msg->getAttributes()['Type']['StringValue']);
        $this->assertSame('the-component-library', $msg->getAttributes()['ComponentLibrary']['StringValue']);
        $this->assertSame('Group A', $msg->getBody()['producerGroup']);
    }

    public function testReheatCache_requiresRight()
    {
        $this->reheatCacheController->method('requireRight')->willThrowException(new AccessDeniedHttpException());
        $this->expectException(AccessDeniedHttpException::class);
        $this->reheatCacheController->reheatCache($this->getSampleRequest('the-component-library', 'Group A'));
        $this->assertNoMessagesInQueue();
    }

    public function testReheatCache_requiresComoponentLibraryInRequestContent()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->reheatCacheController->reheatCache($this->getSampleRequest('', 'Group A'));
        $this->assertNoMessagesInQueue();
    }

    public function testReheatCache_requiresProducerGroupInRequestContent()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->reheatCacheController->reheatCache($this->getSampleRequest('the-component-library', ''));
        $this->assertNoMessagesInQueue();
    }

    private function getSampleRequest(string $componentLibrary, string $producerGroup): Request
    {
        $content = json_encode(
            [
                'componentLibrary' => $componentLibrary,
                'producerGroup' => $producerGroup,
            ]
        );
        $request = new Request([], [], [], [], [], [], $content);
        $request->setMethod('POST');
        return $request;
    }

    private function assertNoMessagesInQueue()
    {
        $this->assertSame(null, $this->queue->receiveMessage());
    }
}
