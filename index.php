<?php

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY nincs beállítva.');
}

require_once __DIR__ . '/app/OpenAIClient.php';
require_once __DIR__ . '/app/EmbeddingClient.php';
require_once __DIR__ . '/app/VectorMath.php';
require_once __DIR__ . '/app/Tool.php';
require_once __DIR__ . '/app/Agent.php';
require_once __DIR__ . '/app/RunResult.php';

$embeddingClient = new EmbeddingClient($apiKey);

$vectorA = $embeddingClient->embed(
    'A user logs in with an email and password.'
);

$vectorB = $embeddingClient->embed(
    'Validate the credentials of the user.'
);

$vectorC = $embeddingClient->embed(
    'Calculate the total price of an invoice.'
);


echo 'Dimensions: ' . count($vectorA) . PHP_EOL;

echo "First 5 values:\n";

foreach (array_slice($vectorA, 0, 5) as $value) {
    echo $value . PHP_EOL;
}

echo 'Dimensions: ' . count($vectorB) . PHP_EOL;

echo "First 5 values:\n";

foreach (array_slice($vectorB, 0, 5) as $value) {
    echo $value . PHP_EOL;
}

echo 'Dimensions: ' . count($vectorC) . PHP_EOL;

echo "First 5 values:\n";

foreach (array_slice($vectorC, 0, 5) as $value) {
    echo $value . PHP_EOL;
}

$vectorMath = new VectorMath();

$similarityAB = $vectorMath->cosineSimilarity(
    $vectorA,
    $vectorB
);

$similarityAC = $vectorMath->cosineSimilarity(
    $vectorA,
    $vectorC
);

echo "A <-> B: {$similarityAB}" . PHP_EOL;
echo "A <-> C: {$similarityAC}" . PHP_EOL;

die();

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
