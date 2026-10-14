<?php

namespace App\Indexing;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

use App\Parsing\PhpAstParser;

class PhpSemanticChunker
{
    public function __construct(private PhpAstParser $parser) {}

    public function chunk(string $code): array
    {
        $ast = $this->parser->parse($code);

        $visitor = new PhpSemanticChunkVisitor($code);

        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);

        return $visitor->getChunks();
    }
}
