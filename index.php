<?php

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY nincs beállítva.');
}

require_once __DIR__ . '/app/OpenAIClient.php';
require_once __DIR__ . '/app/Tool.php';
require_once __DIR__ . '/app/Agent.php';

$client = new OpenAIClient($apiKey);
$tool = new Tool();
$agent = new Agent($client, $tool);

$result = $agent->run(
    'Nézd meg, milyen PHP fájlok vannak a workspace könyvtárban. '
    . 'Keresd meg, melyik fájl tartalmazza a getUserName függvényt, '
    . 'majd készíts egy két mondatos code review-t erről a függvényről.'
);

echo $result . PHP_EOL;
