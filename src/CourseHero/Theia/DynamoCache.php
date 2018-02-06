<?php

namespace CourseHero\TheiaBundle;

use Aws\DynamoDb\DynamoDbClient;
use CourseHero\UtilsBundle\Service\AbstractCourseHeroService;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use Theia\CachingInterface;
use Theia\RenderResult;

/**
 * Class DynamoCache
 * @package CourseHero\TheiaBundle
 */
class DynamoCache implements CachingInterface
{
    /** @var DynamoDbClient */
    protected $dynamoClient;

    /**
     * @var string
     */
    private $theiaCacheTable;


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
    public function inject(string $amazonS3Key, string $amazonS3Secret, string $amazonS3Region, string $theiaCacheTable)
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
     * @inheritDoc
     */
    public function get(string $key)
    {
        $response = $this->dynamoClient->query(
            [
                'KeyConditions' => [
                    'key' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            ['S' => $key],
                        ],
                    ]],
                'TableName' => $this->theiaCacheTable
            ]
        );

        $items = $response['Items'];

        if ($items) {
            $html = '';
            $assets = [];
            foreach ($items as $item) {
                $html = $item['html']['S'];
                $assets = $item['assets']['S'];
            }

            return new RenderResult($html, json_decode($assets, true));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $componentLibrary, string $component, string $key, RenderResult $renderResult)
    {
        $response = $this->dynamoClient->putItem(
            [
                'TableName' => $this->theiaCacheTable,
                'Item' => [
                    'key' => ['S' => $key],
                    'html' => ['S' => $renderResult->getHtml()],
                    'assets' => ['S' => json_encode($renderResult->getAssets())],
                    'componentLibrary' => ['S' => $componentLibrary]
                ],
            ]
        );
    }
}
