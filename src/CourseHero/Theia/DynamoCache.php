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

    /** @var string */
    private $theiaCacheTable;

    protected $amazonS3Key;
    protected $amazonS3Secret;
    protected $amazonS3Region;

    /**
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
     * @inheritDoc
     */
    public function get(string $key)
    {
        $response = $this->dynamoClient->getItem(
            [
                'Key' => [
                    'key' => ['S' => $key],
                ],
                'TableName' => $this->theiaCacheTable
            ]
        );

        $item = $response['Item'];

        if ($item) {
            $html = $item['html']['S'];
            $assets = $item['assets']['S'];

            return new RenderResult($html, json_decode($assets, true));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $componentLibrary, string $component, string $key, RenderResult $renderResult, int $secondsUntilExpires)
    {
        $response = $this->dynamoClient->putItem(
            [
                'TableName' => $this->theiaCacheTable,
                'Item' => [
                    'key' => ['S' => $key],
                    'html' => ['S' => $renderResult->getHtml()],
                    'assets' => ['S' => json_encode($renderResult->getAssets())],
                    'componentLibrary' => ['S' => $componentLibrary],
                    'expirationDate' => ['N' => time() + $secondsUntilExpires],
                ],
            ]
        );
    }
}
