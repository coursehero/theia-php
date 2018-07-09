<?php

namespace CourseHero\Theia\ReheatCache;

abstract class TheiaJobHandler
{
    /** @var JobProcessor */
    protected $processor;

    /** @var string */
    protected $componentLibrary;

    public function __construct(JobProcessor $processor, string $componentLibrary)
    {
        $this->processor = $processor;
        $this->componentLibrary = $componentLibrary;
    }

    abstract public function processNewBuildJob(JobData $data);

    abstract public function processProducerJob(JobData $data);

    /**
     * @param JobData $data
     * @throws \Exception
     */
    public function processRenderJob(JobData $data)
    {
        $this->processor->getClient()->renderAndCache($this->componentLibrary, $data->component, $data->props, true);
    }

    public function getComponentLibrary(): string
    {
        return $this->componentLibrary;
    }

    /**
     * @param string $producerGroup
     * @param array $extra
     * @throws \Exception
     */
    protected function createProducerJob(string $producerGroup, array $extra)
    {
        $data = new JobData();
        $data->componentLibrary = $this->componentLibrary;
        $data->producerGroup = $producerGroup;
        $data->extra = $extra;
        $this->process->getJobCreator()->createProducerJob($data);
    }

    /**
     * @param string $component
     * @param string $props
     * @throws \Exception
     */
    protected function createRenderJob(string $component, string $props)
    {
        $data = new JobData();
        $data->componentLibrary = $this->componentLibrary;
        $data->component = $component;
        $data->props = $props;
        $this->process->getJobCreator()->createRenderJob($data);
    }
}
