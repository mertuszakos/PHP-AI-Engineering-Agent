<?php

namespace App\LLM;

class OpenAIProvider implements ConversationProviderInterface
{
    private ?string $previousResponseId = null;
    private array $tools = [];

    public function __construct(
        private readonly OpenAIClient $client,
        private readonly OpenAIResponseMapper $mapper
    ) {}

    public function startConversation(string $task, array $tools): LLMResponse
    {
        $this->previousResponseId = null;
        $this->tools = $tools;

        $payload = [
            'model' => 'gpt-5.6',
            'reasoning' => [
                'effort' => 'low',
            ],
            'tools' => $this->tools,
            'tool_choice' => 'auto',
            'input' => $task,
        ];

        $response = $this->client->createResponse($payload);

        $responseId = $response['id'] ?? null;

        if (!is_string($responseId) || $responseId === '') {
            throw new \RuntimeException('OpenAI response ID is missing.');
        }

        $this->previousResponseId = $responseId;

        return $this->mapper->map($response);
    }

    public function continueConversation(array $toolResults): LLMResponse
    {
        if ($this->previousResponseId === null) {
            throw new \LogicException('Previous response id not found');
        }

        $payload = [
            'model' => 'gpt-5.6',
            'reasoning' => [
                'effort' => 'low',
            ],
            'tools' => $this->tools,
            'previous_response_id' => $this->previousResponseId,
            'input' => $toolResults,
        ];

        $response = $this->client->createResponse($payload);

        $this->previousResponseId = $response['id'];

        return $this->mapper->map($response);
    }
}
