<?php

require 'vendor/autoload.php';
require 'src/TheiaClient.php';

$componentLibrary = '@coursehero-components/mythos';
$component = 'Greeting';
$props = [
  'name' => 'Connor Clark'
];

$client = new \Theia\Client('localhost:3000', [
  'CH-Auth' => 'courseherobatman'
]);

$html = $client->render($componentLibrary, $component, $props);
echo($html);
