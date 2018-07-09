<?php

namespace CourseHero\Theia\ReheatCache;

abstract class JobCreator
{
    /**
     * @param JobData $data
     */
    public function createJob(JobData $data);

    /**
     * @param JobData $data
     */
    public function createProducerJob(JobData $data)
    {
        $data->type = 'producer-job';
        $this->createJob($data);
    }

    /**
     * @param JobData $data
     */
    public function createRenderJob(JobData $data)
    {
        $data->type = 'render-job';
        $this->createJob($data);
    }
}
