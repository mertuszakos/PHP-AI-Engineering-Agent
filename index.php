<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\OpenAIClient;
use App\EmbeddingClient;
use App\VectorMath;
use App\Tool;
use App\Agent;
use App\RunResult;
use App\SemanticSearch;
use App\RepositoryIndexer;
use App\CodeChunker;
use App\PhpAstParser;
use App\PhpSemanticChunker;

use App\RepositorySemanticIndexer;

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

$results = $semanticSearch->search('process customer payment');

print_r($results);
