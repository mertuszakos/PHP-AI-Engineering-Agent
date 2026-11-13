<?php

namespace App\LLM\Contracts;

interface LLMProviderInterface
{
    public function createResponse(array $payload): array;
}
