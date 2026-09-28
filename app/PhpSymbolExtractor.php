<?php

class PhpSymbolExtractor
{
    public function extract(array $tokens): array
    {
        $symbols = [];
        $prevTokenType = null;
        $searched = ['T_CLASS', 'T_FUNCTION'];

        foreach ($tokens as $token) {
            switch ($token['type']) {
                case 'T_CLASS':
                    $symbols[] = [
                        'type' => 'class',
                        'name' => null,
                        'line' => $token['line'],
                    ];
                    break;
                case 'T_FUNCTION':
                    $symbols[] = [
                        'type' => 'function',
                        'name' => null,
                        'line' => $token['line'],
                    ];
                    break;
                case 'T_STRING':
                    if ($prevTokenType !== null) {
                        $lastSymbol = array_key_last($symbols);
                        $symbols[$lastSymbol]['name'] = $token['text'];
                        $prevTokenType = null;
                    }
                    break;
            }
            if (in_array($token['type'], $searched)) {
                $prevTokenType = $token['type'];
            }
        }

        return $symbols;
    }
}