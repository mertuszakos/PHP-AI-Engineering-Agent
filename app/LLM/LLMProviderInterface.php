<?php

namespace App\LLM;

interface LLMProviderInterface
{
    public function createResponse(array $payload): array;
}
