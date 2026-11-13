<?php

namespace App\LLM\Contracts;

use App\LLM\DTO\LLMResponse;

interface ConversationProviderInterface
{
    public function startConversation(string $task, array $tools): LLMResponse;

    public function continueConversation(array $toolResults): LLMResponse;
}
