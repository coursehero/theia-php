<?php

namespace CourseHero\TheiaBundle\Service;

use CourseHero\TheiaBundle\DynamoCache;
use CourseHero\UtilsBundle\Service\AbstractCourseHeroService;
use CourseHero\UtilsBundle\Service\SlackMessengerService;
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

    /** @var string */
    protected $environment;

    /** @var SlackMessengerService */
    protected $slackMessengerService;

    /**
     * @InjectParams({
     *     "endpoint"        = @Inject("%theia.endpoint%"),
     *     "authKey"         = @Inject("%ch_internal.apiKey%"),
     *     "amazonS3Key"     = @Inject("%amazon_s3.key%"),
     *     "amazonS3Secret"  = @Inject("%amazon_s3.secret%"),
     *     "amazonS3Region"  = @Inject("%amazon_s3.region%"),
     *     "theiaCacheTable" = @Inject("%theia.cache_table%"),
     *     "environment"     = @Inject("%environment%"),
     *     "slackMessengerService" = @Inject(SlackMessengerService::SERVICE_ID),
     * })
     *
     * @param string $endpoint
     * @param string $authKey
     * @param string $amazonS3Key
     * @param string $amazonS3Secret
     * @param string $amazonS3Region
     * @param string $theiaCacheTable
     * @param string $environment
     * @param SlackMessengerService $slackMessengerService
     */
    public function inject(
        string $endpoint,
        string $authKey,
        string $amazonS3Key,
        string $amazonS3Secret,
        string $amazonS3Region,
        string $theiaCacheTable,
        string $environment,
        SlackMessengerService $slackMessengerService
    ) {
        $this->endpoint = $endpoint;
        $this->authKey = $authKey;
        $this->amazonS3Key = $amazonS3Key;
        $this->amazonS3Secret = $amazonS3Secret;
        $this->amazonS3Region = $amazonS3Region;
        $this->theiaCacheTable = $theiaCacheTable;
        $this->environment = $environment;
        $this->slackMessengerService = $slackMessengerService;
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

    public function sendSlackMessage(string $text)
    {
        if ($this->environment !== 'localhost') {
            $this->slackMessengerService->send($text, $this->getSlackChannelName(), 'IRIS', ':eye:');
        }
    }

    protected function getSlackChannelName(): string
    {
        return $this->environment === 'production' ? '#theia-prod' : '#theia-dev';
    }
}
