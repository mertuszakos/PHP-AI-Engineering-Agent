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
    public function __construct(
        private PhpAstParser $parser
    ) {
    }

    public function chunk(string $code): array
    {
        $ast = $this->parser->parse($code);

        $visitor = new class extends NodeVisitorAbstract
        {
            public function enterNode(Node $node)
            {
                echo $node->getType() . PHP_EOL;

                return null;
            }
        };

        $traverser = new NodeTraverser();

        $traverser->addVisitor($visitor);

        $traverser->traverse($ast);

die;

        $chunk = [];

        foreach ($ast as $node) {
            if ($node instanceof Class_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof ClassMethod) {
                        $startLine = $stmt->getStartLine();
                        $endLine = $stmt->getEndLine();

                        $result = $this->extractSource(
                            $code,
                            $startLine,
                            $endLine
                        );

                        $chunk[] = [
                            'type' => 'method',
                            'symbol' => $node->name->toString() . '::' . $stmt->name->toString(),
                            'start_line' => $startLine,
                            'end_line' => $endLine,
                            'content' => $result,
                        ];
                    }
                }
            }

            if ($node instanceof Function_) {
                $startLine = $node->getStartLine();
                $endLine = $node->getEndLine();

                $result = $this->extractSource(
                    $code,
                    $startLine,
                    $endLine
                );

                $chunk[] = [
                    'type' => 'function',
                    'symbol' => $node->name->toString(),
                    'start_line' => $startLine,
                    'end_line' => $endLine,
                    'content' => $result,
                ];
            }
        }

        return $chunk;
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
}