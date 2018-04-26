<?php

namespace CourseHero\TheiaBundle\Command;

abstract class TheiaJobHandler
{
    /** @var string */
    public static $componentLibrary;

    /** @var \Theia\Client */
    protected $theiaClient;

    public function __construct(\Theia\Client $theiaClient)
    {
        $this->theiaClient = $theiaClient;
    }

    abstract public function processNewBuildJob(string $builtAt, string $commitHash);

    abstract public function processProducerJob(string $producerGroup);
}
