<?php

namespace CourseHero\TheiaBundle\Service;

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
     * @var DynamoCacheFactoryService
     */
    private $dynamoCacheFactoryService;

    /**
     * @InjectParams({
     *     "endpoint"   = @Inject("%theia.endpoint%"),
     *     "authKey"    = @Inject("%theia.auth_key%"),
     *     "dynamoCacheFactoryService"  = @Inject(DynamoCacheFactoryService::SERVICE_ID),
     * })
     *
     * @param string $endpoint
     * @param string $authKey
     * @param DynamoCacheFactoryService $dynamoCacheFactoryService
     */
    public function inject(string $endpoint, string $authKey, DynamoCacheFactoryService $dynamoCacheFactoryService)
    {
        $this->endpoint = $endpoint;
        $this->authKey = $authKey;
        $this->dynamoCacheFactoryService = $dynamoCacheFactoryService;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return new \Theia\Client($this->endpoint, $this->dynamoCacheFactoryService->createDynamoCache(), [
            'CH-Auth' => $this->authKey
        ]);
    }
}
