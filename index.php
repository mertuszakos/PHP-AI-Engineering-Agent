<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\LLM\OpenAI\OpenAIClient;
use App\LLM\Ollama\OllamaClient;
use App\LLM\OpenAI\EmbeddingClient;
use App\LLM\OpenAI\OpenAIProvider;
use App\LLM\Ollama\OllamaProvider;
use App\LLM\OpenAI\OpenAIResponseMapper;
use App\LLM\Ollama\OllamaResponseMapper;
use App\Search\VectorMath;
use App\Search\SemanticSearch;
use App\Tools\Tool;
use App\Agent\Agent;
use App\Indexing\RepositoryIndexer;
use App\Indexing\RepositorySemanticIndexer;
use App\Indexing\PhpSemanticChunker;
use App\Parsing\PhpAstParser;

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

$client = new OllamaClient('http://host.docker.internal:11434');

$mapper = new OllamaResponseMapper();
$provider = new OllamaProvider($client, $mapper);

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
