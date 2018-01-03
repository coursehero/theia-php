<?php

require 'vendor/autoload.php';
require 'src/Client.php';

$componentLibrary = '@coursehero-components/mythos';
$component = 'Greeting';
$props = [
  'name' => 'Connor Clark',
];

$client = new \Theia\Client('localhost:3000', [
  'CH-Auth' => 'courseherobatman'
]);

$html = $client->render($componentLibrary, $component, $props);
echo($html . "\n");

$html = $client->render($componentLibrary, $component, json_encode($props));
echo($html . "\n");

$html = $client->renderAndCache($componentLibrary, $component, $props);
echo($html . "\n");

$html = $client->renderAndCache($componentLibrary, $component, json_encode($props));
echo($html . "\n");
