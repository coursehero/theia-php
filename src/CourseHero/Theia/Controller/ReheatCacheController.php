<?php

namespace CourseHero\TheiaBundle\Controller;

use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\QueueBundle\Constant\Queue;
use CourseHero\QueueBundle\Service\QueueService;
use CourseHero\TheiaBundle\Command\ReheatCacheJobCreator;
use CourseHero\UserBundle\Entity\UserRight;
use CourseHero\UtilsBundle\Controller\AbstractCourseHeroController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ReheatCacheController extends AbstractCourseHeroController
{
    /**
     * @ApiDoc(
     *      section="Theia Reheat Cache",
     *      description="Reheats cache for given component library and producer group",
     *      parameters={
     *          {"name"="componentLibrary", "dataType"="string", "required"=true},
     *          {"name"="producerGroup", "dataType"="string", "required"=true}
     *     },
     *     statusCodes={
     *          204="Successful",
     *          400="Bad Request",
     *          403="Access Denied"
     *     }
     * )
     * @Rest\Post("/api/v1/theia-reheat-cache/", name="theia_reheat_cache")
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function reheatCache(Request $request)
    {
        $this->requireRight(UserRight::PROCO_ADMIN_ACCESS_RIGHT);

        $requestBody = json_decode($request->getContent(), true);

        $componentLibrary = $requestBody['componentLibrary'];
        if (!$componentLibrary) {
            throw new BadRequestHttpException('componentLibrary parameter is required');
        }

        $producerGroup = $requestBody['producerGroup'];
        if (!$producerGroup) {
            throw new BadRequestHttpException('producerGroup parameter is required');
        }

        $jobCreator = new ReheatCacheJobCreator($this->getQueue());
        $jobCreator->createProducerJob($componentLibrary, $producerGroup);
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    protected function getQueue(): QueueInterface
    {
        return $this->getQueueService()->getQueue($this->getQueueName());
    }

    protected function getQueueName(): string
    {
        $environment = $this->getParameter('environment');
        return 'production' === $environment ? Queue::THEIA_REHEAT_JOBS : Queue::THEIA_REHEAT_JOBS_DEV;
    }

    protected function getQueueService(): QueueService
    {
        return $this->get(QueueService::SERVICE_ID);
    }
}
