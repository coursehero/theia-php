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

    /** @var string */
    protected $theiaCacheTable;

    /** @var string */
    protected $amazonS3Key;

    /** @var string */
    protected $amazonS3Secret;

    /** @var string */
    protected $amazonS3Region;

    /**
     * @InjectParams({
     *     "endpoint"   = @Inject("%theia.endpoint%"),
     *     "authKey"    = @Inject("%theia.auth_key%"),
     *     "amazonS3Key"    = @Inject("%amazon_s3.key%"),
     *     "amazonS3Secret" = @Inject("%amazon_s3.secret%"),
     *     "amazonS3Region" = @Inject("%amazon_s3.region%"),
     *     "theiaCacheTable"= @Inject("%theia.cache_table%"),
     * })
     *
     * @param string $endpoint
     * @param string $authKey
     * @param string $amazonS3Key
     * @param string $amazonS3Secret
     * @param string $amazonS3Region
     * @param string $theiaCacheTable
     */
    public function inject(string $endpoint, string $authKey, string $amazonS3Key,
        string $amazonS3Secret, string $amazonS3Region, string $theiaCacheTable)
    {
        $this->endpoint = $endpoint;
        $this->authKey = $authKey;
        $this->amazonS3Key = $amazonS3Key;
        $this->amazonS3Secret = $amazonS3Secret;
        $this->amazonS3Region = $amazonS3Region;
        $this->theiaCacheTable = $theiaCacheTable;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        $dynamoCache = new DynamoCache($this->amazonS3Key, $this->amazonS3Secret, $this->amazonS3Region, $this->theiaCacheTable);

        return new \Theia\Client(
            $this->endpoint,
            $dynamoCache,
            [
                'CH-Auth' => $this->authKey,
            ]
        );
    }
}
