<?php

namespace CourseHero\TheiaBundle\Service;

use CourseHero\TheiaBundle\DynamoCache;
use CourseHero\UtilsBundle\Service\AbstractCourseHeroService;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use Theia\Client;

/**
 * @Service(TheiaProviderService::SERVICE_ID)
 */
class TheiaProviderService extends AbstractCourseHeroService
{
    const SERVICE_ID = 'course_hero.theia.service.provider';

    /** @var string */
    private $endpoint;

    /** @var string */
    private $authKey;

    /**
     * @InjectParams({
     *     "endpoint"   = @Inject("%theia.endpoint%"),
     *     "authKey"    = @Inject("%theia.auth_key%"),
     * })
     *
     * @param string $endpoint
     * @param string $authKey
     */
    public function inject(string $endpoint, string $authKey)
    {
        $this->endpoint = $endpoint;
        $this->authKey = $authKey;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return new \Theia\Client($this->endpoint, new DynamoCache(), [
            'CH-Auth' => $this->authKey
        ]);
    }
}
