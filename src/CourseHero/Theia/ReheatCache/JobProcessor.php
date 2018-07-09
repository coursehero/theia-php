<?php

namespace CourseHero\Theia\ReheatCache;

class JobProcessor
{
    /** @var JobCreator */
    protected $creator;

    /** @var \Theia\Client */
    protected $client;

    /** @var array */
    protected $handlers;

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
    protected function process(JobData $data)
    {
        $jobHandler = $this->handlers[$data->componentLibrary];

        // $attrsAsString = json_encode($attrs, JSON_PRETTY_PRINT);
        // $this->write("Processing $data->type with $attrsAsString");
        // if ($type !== 'render-job') {
        //     $bodyAsString = json_encode($body, JSON_PRETTY_PRINT);
        //     $this->write("Body: $bodyAsString");
        // }

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
