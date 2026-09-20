<?php

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY nincs beállítva.');
}

require_once __DIR__ . '/app/OpenAIClient.php';
require_once __DIR__ . '/app/Tool.php';
require_once __DIR__ . '/app/Agent.php';
require_once __DIR__ . '/app/RunResult.php';

$client = new OpenAIClient($apiKey);
$tool = new Tool();
$agent = new Agent($client, $tool);

$result = $agent->run(
    'Review the uncommitted changes in the workspace project. '
    . 'Identify potential bugs, security issues, and code quality problems. '
    . 'Keep the review concise.'
);

echo "Tool calls:\n";

foreach ($result->toolCalls as $tool) {
    $arguments = json_encode($tool["arguments"]);
    echo "- {$tool["name"]} {$arguments}\n";
}

echo "--- Run metrics ---\n";
echo "API calls: {$result->apiCalls}\n";
echo "Input tokens: {$result->inputTokens}\n";
echo "Output tokens: {$result->outputTokens}\n";
echo "Reasoning tokens: {$result->reasoningTokens}\n";
echo "Total tokens: {$result->getTotalTokens()}\n";

echo "--- Result: ---\n";
echo $result->answer;
