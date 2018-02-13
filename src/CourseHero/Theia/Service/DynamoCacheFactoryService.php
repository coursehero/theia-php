<?php

namespace CourseHero\TheiaBundle\Service;

use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use Aws\DynamoDb\DynamoDbClient;
use CourseHero\TheiaBundle\DynamoCache;

/**
 * @Service(DynamoCacheFactoryService::SERVICE_ID)
 */
class DynamoCacheFactoryService
{
    const SERVICE_ID = 'course_hero.service.dynamo.cache.factory';

    /** @var DynamoDbClient */
    protected $dynamoClient;

    /**
     * @var string
     */
    protected $theiaCacheTable;
    protected $amazonS3Key;
    protected $amazonS3Secret;
    protected $amazonS3Region;

    /**
     * @InjectParams({
     *     "amazonS3Key"    = @Inject("%amazon_s3.key%"),
     *     "amazonS3Secret" = @Inject("%amazon_s3.secret%"),
     *     "amazonS3Region" = @Inject("%amazon_s3.region%"),
     *     "theiaCacheTable"= @Inject("%theia.cache_table%"),
     * })
     * @param string $amazonS3Key
     * @param string $amazonS3Secret
     * @param string $amazonS3Region
     * @param string $theiaCacheTable
     */
    public function __construct(string $amazonS3Key, string $amazonS3Secret, string $amazonS3Region, string $theiaCacheTable)
    {
        $this->amazonS3Key = $amazonS3Key;
        $this->amazonS3Secret = $amazonS3Secret;
        $this->amazonS3Region = $amazonS3Region;
        $this->theiaCacheTable = $theiaCacheTable;

        /** @var DynamoDbClient $client */
        $this->dynamoClient = DynamoDbClient::factory(
            [
                "key" => $this->amazonS3Key,
                "secret" => $this->amazonS3Secret,
                "region" => $this->amazonS3Region,
            ]
        );
    }

    /**
     * @return DynamoCache
     */
    public function createDynamoCache()
    {
        return new DynamoCache($this->amazonS3Key, $this->amazonS3Secret, $this->amazonS3Region, $this->theiaCacheTable);
    }
}