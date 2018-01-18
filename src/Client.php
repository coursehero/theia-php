<?php

namespace Theia;

/**
 * Class Client
 * @package Theia
 */
class Client {
    /** @var string */
    private $endpoint;

    /** @var ICachingStrategy */
    private $cachingStrategy;

    /** @var array */
    private $headers;

    /**
     * Client constructor.
     * @param string $endpoint
     * @param ICachingStrategy $cachingStrategy
     * @param ?array $headers
     */
    public function __construct(string $endpoint, ICachingStrategy $cachingStrategy = null, array $headers = [])
    {
        $this->endpoint = $endpoint;
        $this->cachingStrategy = $cachingStrategy;
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
     * @return RenderResult
     */
    public function renderAndCache(string $componentLibrary, string $component, $props): RenderResult
    {
        if (is_array($props)) {
            $this->ksortRecursive($props);
            $propsAsString = json_encode($props);
        } else {
            $propsAsString = $props;
        }

        $hash = hash('md4', $propsAsString);
        $key = "$componentLibrary/$component/$hash";
        $cachedRenderResult = $this->cachingStrategy->get($componentLibrary, $component, $key);

        if ($cachedRenderResult) {
            return $cachedRenderResult;
        }

        $renderResult = $this->render($componentLibrary, $component, $propsAsString);
        $this->cachingStrategy->set($componentLibrary, $component, $key, $renderResult);

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
