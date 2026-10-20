<?php

namespace App\LLM;

interface ConversationProviderInterface
{
    public function startConversation(string $task, array $tools): LLMResponse;

    public function continueConversation(array $toolResults): LLMResponse;
}
