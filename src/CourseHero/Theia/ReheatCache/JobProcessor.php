<?php

namespace CourseHero\Theia\ReheatCache;

use CourseHero\Theia\Client;

class JobProcessor
{
    /** @var JobCreator */
    protected $creator;

    /** @var Client */
    protected $client;

    /** @var array */
    protected $handlers = [];

    public function __construct(JobCreator $creator, Client $client)
    {
        $this->creator = $creator;
        $this->client = $client;
    }

    public function registerJobHandler(JobHandler $handler)
    {
        $this->handlers[$handler->getComponentLibrary()] = $handler;
    }

    /**
     * @param JobData $data
     * @throws \Exception
     */
    public function process(JobData $data)
    {
        $jobHandler = $this->handlers[$data->componentLibrary];

        switch ($data->type) {
            case 'new-build-job':
                $jobHandler->processNewBuildJob($data);
                break;
            case 'producer-job':
                $jobHandler->processProducerJob($data);
                break;
            case 'render-job':
                $jobHandler->processRenderJob($data);
                break;
            default:
                throw new \Exception("unexpected job type: $data->type");
        }
    }

    public function getJobCreator(): JobCreator
    {
        return $this->creator;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
