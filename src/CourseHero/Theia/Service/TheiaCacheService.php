<?php

namespace CourseHero\TheiaBundle\Service;


use Aws\DynamoDb\DynamoDbClient;
use CourseHero\UtilsBundle\Service\AbstractCourseHeroService;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use Theia\CachingInterface;
use Theia\RenderResult;

/**
 * Class TheiaCacheService
 * @package CourseHero\UtilsBundle\Service
 * @Service(TheiaCacheService::SERVICE_ID)
 */
class TheiaCacheService extends AbstractCourseHeroService implements CachingInterface
{
    const SERVICE_ID = 'course_hero.study_guide.service.theia_cache';

    /** @var DynamoDbClient */
    protected $dynamoClient;


    protected $amazonS3Key;
    protected $amazonS3Secret;
    protected $amazonS3Region;


    /**
     * @InjectParams({
     *     "amazonS3Key"    = @Inject("%amazon_s3.key%"),
     *     "amazonS3Secret" = @Inject("%amazon_s3.secret%"),
     *     "amazonS3Region" = @Inject("%amazon_s3.region%"),
     * })
     * @param string $amazonS3Key
     * @param string $amazonS3Secret
     * @param string $amazonS3Region
     */
    public function inject(string $amazonS3Key, string $amazonS3Secret, string $amazonS3Region)
    {
        $this->amazonS3Key = $amazonS3Key;
        $this->amazonS3Secret = $amazonS3Secret;
        $this->amazonS3Region = $amazonS3Region;

        /** @var DynamoDbClient $client */
        $this->dynamoClient = DynamoDbClient::factory(
            [
                "key" => $this->amazonS3Key,
                "secret" => $this->amazonS3Secret,
                "region" => $this->amazonS3Region,
            ]
        );
    }

    const TABLE_NAME = "dev_theia";


    /**
     * @inheritDoc
     */
    public function get(string $key)
    {
        var_dump("inside get dynamo function");
        var_dump($key);

       /* $response = $this->dynamoClient->getItem(
            [
                'Key' => [
                    'key' => ['S' => $key],
                ],
                'TableName' => self::TABLE_NAME
            ]
        );
*/
        $response = $this->dynamoClient->query(
            [
                'KeyConditions' => [
                    'key' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            ['S' => $key],
                        ],
                    ]],
                    'TableName' => self::TABLE_NAME
                ]
        );
      //  var_dump("Response= ".$response);
        $items = $response['Items'];

        if ($items) {
            var_dump("Items= ".$items);
            $html = '';
            $assets = [];
            foreach ($items as $item) {
                $html = $item['html']['S'];
                $assets = $item['assets']['S'];
            }
           // var_dump("Value of html is: ".$html." done");
            if ($html != '') {
                return new RenderResult($html, json_decode($assets, true));
            }
        }

        var_dump("returning null");
        return null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $componentLibrary, string $component, string $key, RenderResult $renderResult)
    {
        var_dump("inside set dynamao");
        $response = $this->dynamoClient->putItem(
            [
                'TableName' => self::TABLE_NAME,
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