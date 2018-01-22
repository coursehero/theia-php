<?php

namespace CourseHero\TheiaBundle\Service;

use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use Theia\Client;

/**
 * @Service(TheiaProviderService::SERVICE_ID)
 */
class TheiaProviderService extends \CourseHero\UtilsBundle\Service\AbstractCourseHeroService
{

    const SERVICE_ID = 'course_hero.theia.service.provider';

    /** @var string */
    private $endpoint;

    /** @var string */
    private $authKey;

    /**
     * @var TheiaCacheService
     */
    private $theiaCacheService;

    /**
     * @var Client
     */
    private static $client;

    /**
     * @InjectParams({
     *     "endpoint"   = @Inject("%theia.endpoint%"),
     *     "authKey"    = @Inject("%theia.auth_key%"),
     *     "theiaCache" = @Inject(TheiaCacheService::SERVICE_ID),
     * })
     *
     * @param string $endpoint
     * @param string $authKey
     * @param TheiaCacheService $theiaCacheService
     */
    public function inject(string $endpoint, string $authKey, TheiaCacheService $theiaCacheService)
    {
        $this->endpoint = $endpoint;
        $this->authKey = $authKey;
        $this->theiaCacheService = $theiaCacheService;
    }

    /**
     * @return Client
     */
    public function get()
    {
        if (!self::$client) {
            self::$client = new Client(
                $this->endpoint, $this->theiaCacheService, [
                'CH-Auth' => $this->authKey,
            ]
            );
        }

        return self::$client;
    }


}
