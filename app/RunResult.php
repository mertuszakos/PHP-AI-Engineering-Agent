<?php

class RunResult
{
    public function __construct(
        public readonly string $answer,
        public readonly array $toolCalls,
        public readonly int $apiCalls,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly int $reasoningTokens,
    ) {
    }

    public function getTotalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}