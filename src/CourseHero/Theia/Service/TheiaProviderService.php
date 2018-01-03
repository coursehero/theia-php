<?php

namespace CourseHero\TheiaBundle\Service;

use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;

/**
 * @Service(TheiaProviderService::SERVICE_ID)
 */
class TheiaProviderService extends \CourseHero\UtilsBundle\Service\AbstractCourseHeroService {
    
    const SERVICE_ID = 'course_hero.theia.service.provider';

    /** @var string */
    private $endpoint;

    /** @var string */
    private $authKey;

    /**
     * @InjectParams({
     *        "endpoint"                = @Inject("%theia.endpoint%"),
     *        "authKey"                 = @Inject("%theia.auth_key%")
     * })
     *
     * @param string $endpoint
     * @param string $authKey
     */
    public function inject(string $endpoint, string $authKey) {
        $this->endpoint = $endpoint;
        $this->authKey = $authKey;
    }

    /**
     * @return \Theia\Client
     */
    public function get() {
        return new \Theia\Client($this->endpoint, [
            'CH-Auth' => $this->authKey
        ]);
    }
}
