<?php

class PhpTokenizer
{
    public function tokenize(string $code): array
    {
        $normalize = [];
        $tokens = token_get_all($code);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $normalize[] = [
                    'type' => token_name($token[0]),
                    'text' => $token[1],
                    'line' => $token[2],
                ];
            } else {
                $normalize[] = [
                    'type' => 'CHAR',
                    'text' => $token,
                    'line' => null,
                ];
            }
        }

        return $normalize;
    }
}