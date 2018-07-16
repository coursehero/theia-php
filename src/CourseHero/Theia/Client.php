<?php

namespace CourseHero\Theia;

/**
 * Class Client
 * @package CourseHero\Theia
 */
class Client {
    const THIRTY_DAYS = 30 * 24 * 60 * 60;

    /** @var string */
    private $endpoint;

    /** @var CachingInterface */
    private $cachingInterface;

    /** @var array */
    private $headers;

    /**
     * Client constructor.
     * @param string $endpoint
     * @param CachingInterface $cachingInterface
     * @param ?array $headers
     */
    public function __construct(string $endpoint, CachingInterface $cachingInterface = null, array $headers = [])
    {
        $this->endpoint = $endpoint;
        $this->cachingInterface = $cachingInterface;
        $this->headers = $headers;
    }

    /**
     * @param string $componentLibrary
     * @param string $component
     * @param string|array $props
     * @return RenderResult
     */
    public function render(string $componentLibrary, string $component, $props): RenderResult
    {
        $options = [
            'headers' => $this->headers,
            'query' => [
                'componentLibrary' => $componentLibrary,
                'component' => $component
            ]
        ];

        if (is_array($props)) {
            $options['json'] = $props;
        } else {
            $options['body'] = $props;
            $options['headers']['Content-Type'] = 'application/json';
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->post($this->endpoint . '/render', $options);
        $html = $response->getBody()->getContents();
        $assets = json_decode($response->getHeader('Theia-Assets')[0], true);

        return new RenderResult($html, $assets);
    }

    /**
     * @param string $componentLibrary
     * @param string $component
     * @param string|array $props
     * @param bool $force - always render even if key is already in cache
     * @param int $secondsUntilExpires - Number of seconds until item will expire in
     *      cache and be removed from the cache.
     * @return RenderResult
     */
    public function renderAndCache(
        string $componentLibrary,
        string $component,
        $props,
        bool $force = false,
        int $secondsUntilExpires = self::THIRTY_DAYS
    ): RenderResult {
        if (is_array($props)) {
            $this->ksortRecursive($props);
            $propsAsString = json_encode($props);
        } else {
            $propsAsString = $props;
        }

        $hash = hash('md4', $propsAsString);
        $key = "$componentLibrary/$component/$hash";
        if (!$force) {
            $cachedRenderResult = $this->cachingInterface->get($key);
            if ($cachedRenderResult) {
                $cachedRenderResult->setRetrievedFromCache(true);
                return $cachedRenderResult;
            }
        }

        $renderResult = $this->render($componentLibrary, $component, $propsAsString);
        $this->cachingInterface->set($componentLibrary, $component, $key, $renderResult, $secondsUntilExpires);
        $renderResult->setRetrievedFromCache(false);

        return $renderResult;
    }

    /**
     * @param array $array
     */
    private function ksortRecursive(&$array)
    {
        if (is_array($array)) {
            ksort($array);
            array_walk($array, [$this, 'ksortRecursive']);
        }
    }
}
