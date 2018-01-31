<?php

require 'vendor/autoload.php';
require 'src/Client.php';
require 'src/CachingInterface.php';

$componentLibrary = '@coursehero-components/mythos';
$component = 'Greeting';
$props = [
  'name' => 'Connor Clark',
];

class FakeCache implements \Theia\CachingInterface
{
    public function get(string $key)
    {
        return null;
    }

    public function set(string $componentLibrary, string $component, string $key, \Theia\RenderResult $renderResult)
    {
    }
}

$client = new \Theia\Client('localhost:3000', new FakeCache(), [
  'CH-Auth' => 'courseherobatman'
]);

$renderResult = $client->render($componentLibrary, $component, $props);
echo($renderResult->getHtml() . "\n");
echo(json_encode($renderResult->getAssets()['javascripts']) . "\n");
echo(json_encode($renderResult->getAssets()['stylesheets']) . "\n");

$renderResult = $client->render($componentLibrary, $component, json_encode($props));
echo($renderResult->getHtml() . "\n");

$renderResult = $client->renderAndCache($componentLibrary, $component, $props);
echo($renderResult->getHtml() . "\n");

$renderResult = $client->renderAndCache($componentLibrary, $component, json_encode($props));
echo($renderResult->getHtml() . "\n");
