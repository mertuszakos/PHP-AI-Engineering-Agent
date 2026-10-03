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

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
    throw new RuntimeException('OPENAI_API_KEY nincs beállítva.');
}

$code = <<<'PHP'
<?php

namespace App\Services;

function helper(): void
{
}

class AuthService
{
    public function login(): void
    {
    }
}
PHP;

$parser = new PhpAstParser();
$chunker = new PhpSemanticChunker($parser);

$chunks = $chunker->chunk($code);

print_r($chunks);
