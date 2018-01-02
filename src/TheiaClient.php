<?php

namespace Theia;

/**
 * Class Client
 * @package Theia
 */
class Client {
    /** @var string */
    private $endpoint;

    /** @var array */
    private $headers;

    /**
     * Client constructor.
     * @param string $endpoint
     * @param array $headers
     */
    public function __construct(string $endpoint, array $headers = [])
    {
        $this->endpoint = $endpoint;
        $this->headers = $headers;
    }

    /**
     * @param string $componentLibrary
     * @param string $component
     * @param array $props
     * @return string
     */
    public function render(string $componentLibrary, string $component, array $props): string
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->post(
            $this->endpoint . '/render',
            [
                'headers' => $this->headers,
                'query' => [
                    'componentLibrary' => $componentLibrary,
                    'component' => $component
                ],
                'form_params' => $props
            ]
        );
        
        return $response->getBody()->getContents();
    }

    /**
     * @param string $key
     * @param string $componentLibrary
     * @param string $component
     * @param array $props
     * @return string
     */
    public function renderAndCache(string $key, string $componentLibrary, string $component, array $props): string
    {
        return 'TODO: Not Implemented';
    }

    /**
     * @param string $key
     * @param string $componentLibrary
     * @param string $component
     * @return null|string
     */
    public function loadFromCache(string $key, string $componentLibrary, string $component): ?string
    {
        return 'TODO: Not Implemented';
    }
}
