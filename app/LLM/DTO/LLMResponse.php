<?php

namespace App\LLM\DTO;

class LLMResponse
{
    public function __construct(
        public readonly ?string $text,
        public readonly array $toolCalls,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly int $reasoningTokens,
    ) {}
}