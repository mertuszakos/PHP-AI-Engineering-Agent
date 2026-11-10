<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\LLM\OpenAIClient;
use App\LLM\OllamaClient;
use App\LLM\EmbeddingClient;
use App\LLM\OpenAIProvider;
use App\LLM\OllamaProvider;
use App\LLM\OpenAIResponseMapper;
use App\LLM\OllamaResponseMapper;
use App\Search\VectorMath;
use App\Search\SemanticSearch;
use App\Tools\Tool;
use App\Agent\Agent;
use App\Indexing\RepositoryIndexer;
use App\Indexing\RepositorySemanticIndexer;
use App\Indexing\PhpSemanticChunker;
use App\Parsing\PhpAstParser;

$client = new OllamaClient(
    'http://host.docker.internal:11434'
);

$mapper = new OllamaResponseMapper();

$provider = new OllamaProvider($client, $mapper);

$response = $provider->startConversation(
    'Explain dependency injection in PHP in a few words',
    []
);

echo $response->text;

die;

$response = $client->chat([
    'model' => 'qwen3.5:9b',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'What is PHP?',
        ],
    ],
    'stream' => false,
    'think' => false,
]);

echo $response['message']['content'] ?? 'No response';

die();

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY nincs beállítva.');
}

$indexer = new RepositoryIndexer("workspace");
$parser = new PhpAstParser();
$chunker = new PhpSemanticChunker($parser);
$response = new RepositorySemanticIndexer($indexer, $chunker);
$chunk = $response->index();

$embeddingClient = new EmbeddingClient($apiKey);
$vectorMath = new VectorMath();

$semanticSearch = new SemanticSearch(
    $embeddingClient,
    $vectorMath
);

$semanticSearch->index($chunk);

$client = new OpenAIClient($apiKey);

$mapper = new OpenAIResponseMapper();
$provider = new OpenAIProvider($client, $mapper);

$tools = new Tool($semanticSearch);
$agent = new Agent($provider, $tools);

$result = $agent->run(
    'Find the method responsible for user authentication. ' .
        'Explain any security problems in its implementation. ' .
        'Use semantic_search to locate the relevant code. ' .
        'Include the file name and line numbers in your answer.'
);

echo $result->answer;
print_r($result->toolCalls);
