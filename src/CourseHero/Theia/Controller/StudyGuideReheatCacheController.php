<?php

namespace CourseHero\TheiaBundle\Controller;

use CourseHero\QueueBundle\Component\QueueInterface;
use CourseHero\QueueBundle\Constant\Queue;
use CourseHero\QueueBundle\Service\QueueService;
use CourseHero\TheiaBundle\Command\ReheatCacheJobCreator;
use CourseHero\TheiaBundle\Command\StudyGuideTheiaJobHandler;
use CourseHero\UtilsBundle\Controller\AbstractCourseHeroController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StudyGuideReheatCacheController extends AbstractCourseHeroController
{
    /**
     * @Rest\Post("/api/v1/study-guides/{courseName}/reheat-cache/", name="study_guide_reheat_cache")
     * @param Request $request
     * @param string $courseName
     * @return Response
     * @throws \Exception
     */
    public function reheatCache(Request $request, string $courseName)
    {
        $jobCreator = new ReheatCacheJobCreator($this->getQueue());
        $jobCreator->createProducerJob(StudyGuideTheiaJobHandler::$componentLibrary, $courseName);
        return new Response('', Response::HTTP_OK);
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
