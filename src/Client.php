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
     * @param string|array $props
     * @return string
     */
    public function render(string $componentLibrary, string $component, $props): string
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
        return $response->getBody()->getContents();
    }

    /**
     * @param string $componentLibrary
     * @param string $component
     * @param string|array $props
     * @return string
     */
    public function renderAndCache(string $componentLibrary, string $component, $props): string
    {
        if (is_array($props)) {
            $this->ksortRecursive($props);
            $propsAsString = json_encode($props);
        } else {
            $propsAsString = $props;
        }

        $hash = hash('md4', $propsAsString);
        $key = "$componentLibrary/$component/$hash";
        echo($key . "\n");

        // TODO:
        // if (exists?($key)) load from cache ...
        // else $this->render($componentLibrary, $component, $propsAsString) and cache that

        return 'TODO: Not Implemented';
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
