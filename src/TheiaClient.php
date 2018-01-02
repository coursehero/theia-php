<?php

namespace Theia;

class Client {
    private $host;

    private $headers;

    public function __construct(string $host, array $headers = [])
    {
        $this->host = $host;
        $this->headers = $headers;
    }

    public function render(string $componentLibrary, string $component, array $props): string
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->post(
            $this->host . '/render',
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

    public function renderAndCache(string $key, string $componentLibrary, string $component, array $props): string
    {
        return 'TODO: Not Implemented';
    }

    public function loadFromCache(string $key, string $componentLibrary, string $component): ?string
    {
        return 'TODO: Not Implemented';
    }
}
