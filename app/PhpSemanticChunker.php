<?php

namespace App;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;

class PhpSemanticChunker
{
    public function __construct(
        private PhpAstParser $parser
    ) {
    }

    public function chunk(string $code): array
    {
        $ast = $this->parser->parse($code);
        $chunk = [];

        foreach ($ast as $node) {
            echo $node->getType() . PHP_EOL;

            if ($node instanceof Class_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof ClassMethod) {
                        $startLine = $stmt->getStartLine();
                        $endLine = $stmt->getEndLine();

                        $lines = preg_split('/\R/', $code);
                        $selectedLines = array_slice(
                            $lines,
                            $startLine - 1,
                            $endLine - $startLine + 1
                        );
                        $result = implode("\n", $selectedLines);

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
        }

        return $chunk;
    }
}