<?php

namespace CourseHero\TheiaBundle\Command;

abstract class TheiaJobHandler
{
    /** @var string */
    public static $componentLibrary;

    /** @var \Theia\Client */
    protected $theiaClient;

    /** @var ReheatCacheJobCreator */
    protected $jobCreator;

    public function __construct(\Theia\Client $theiaClient, ReheatCacheJobCreator $jobCreator)
    {
        $this->theiaClient = $theiaClient;
        $this->jobCreator = $jobCreator;
    }

    /**
     * @param string $producerGroup
     * @param array $jobParams
     * @throws \Exception
     */
    protected function createProducerJob(string $producerGroup, array $jobParams)
    {
        $this->jobCreator->createProducerJob(static::$componentLibrary, $producerGroup, $jobParams);
    }

    /**
     * @param string $component
     * @param string $props
     * @throws \Exception
     */
    protected function createRenderJob(string $component, string $props)
    {
        $this->jobCreator->createRenderJob(static::$componentLibrary, $component, $props);
    }

    abstract public function processNewBuildJob(string $builtAt, string $commitHash);

    abstract public function processProducerJob(string $producerGroup, array $producerParams);
}
