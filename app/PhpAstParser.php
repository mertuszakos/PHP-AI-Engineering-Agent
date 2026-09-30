<?php

namespace App;

use PhpParser\ParserFactory;

class PhpAstParser
{
    public function parse(string $code): array
    {
        $parserFactory = new ParserFactory();
        $parser = $parserFactory->createForNewestSupportedVersion();

        $ast = $parser->parse($code);

        return $ast ?? [];
    }
}