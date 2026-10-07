<?php

namespace App;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class PhpSemanticChunker
{
    public function __construct(private PhpAstParser $parser) {}

    public function chunk(string $code): array
    {
        $ast = $this->parser->parse($code);

        $visitor = new class($code) extends NodeVisitorAbstract
        {
            private string $code;
            private array $chunks = [];
            private ?string $currentClass = null;

            public function __construct(string $code)
            {
                $this->code = $code;
            }

            public function getChunks(): array
            {
                return $this->chunks;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Class_) {
                    $this->currentClass = $node->name->toString();
                }

                if ($node instanceof ClassMethod) {
                    $this->addChunk(
                        $node,
                        'method',
                        $this->currentClass . '::' .  $node->name->toString()
                    );
                }

                if ($node instanceof Function_) {
                    $this->addChunk(
                        $node,
                        'function',
                        $node->name->toString()
                    );
                }

                return null;
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Class_) {
                    $this->currentClass = null;
                }
            }

            private function addChunk(
                Node $node,
                string $type,
                string $symbol
            ): void {
                $startLine = $node->getStartLine();
                $endLine = $node->getEndLine();
                $result = $this->extractSource(
                    $this->code,
                    $startLine,
                    $endLine
                );

                $this->chunks[] = [
                    'type' => $type,
                    'symbol' => $symbol,
                    'start_line' => $startLine,
                    'end_line' => $endLine,
                    'content' => $result,
                ];
            }

            private function extractSource(
                string $code,
                int $startLine,
                int $endLine
            ): string {
                $lines = preg_split('/\R/', $code);
                $selectedLines = array_slice(
                    $lines,
                    $startLine - 1,
                    $endLine - $startLine + 1
                );

                return implode("\n", $selectedLines);
            }
        };

        $traverser = new NodeTraverser();

        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);

        return $visitor->getChunks();
    }
}
