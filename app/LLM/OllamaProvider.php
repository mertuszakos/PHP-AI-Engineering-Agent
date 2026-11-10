<?php

namespace App\LLM;

class OllamaProvider implements ConversationProviderInterface
{
    private array $tools = [];
    private array $messages = [];
    private array $pendingToolCalls = [];

    public function __construct(
        private readonly OllamaClient $client,
        private readonly OllamaResponseMapper $mapper
    ) {}

    public function startConversation(string $task, array $tools): LLMResponse
    {
        $this->pendingToolCalls = [];
        $this->tools = $this->mapTools($tools);
        $this->messages = [
            [
                'role' => 'user',
                'content' => $task,
            ],
        ];

        $payload = [
            'model' => 'qwen3.5:9b',
            'messages' => $this->messages,
            'tools' => $this->tools,
            'stream' => false,
            'think' => false,
        ];

        $response = $this->client->chat($payload);

        $this->messages[] = $response['message'];

        $mapper = $this->mapper->map($response);

        foreach ($mapper->toolCalls as $tool) {
            $this->pendingToolCalls[$tool['call_id']] = $tool['name'];
        }

        return $mapper;
    }

    public function continueConversation(array $toolResults): LLMResponse
    {
        foreach ($toolResults as $result) {
            $callId = $result['call_id'];
            $toolName = $this->pendingToolCalls[$callId] ?? null;

            if ($toolName === null) {
                throw new \RuntimeException(
                    "Unknown tool call ID: {$callId}"
                );
            }

            $this->messages[] = [
                'role' => 'tool',
                'tool_name' => $toolName,
                'content' => $result['output']
            ];

            unset($this->pendingToolCalls[$callId]);
        }

        $payload = [
            'model' => 'qwen3.5:9b',
            'messages' => $this->messages,
            'tools' => $this->tools,
            'stream' => false,
            'think' => false,
        ];

        $response = $this->client->chat($payload);

        $this->messages[] = $response['message'];

        $mapper = $this->mapper->map($response);

        foreach ($mapper->toolCalls as $tool) {
            $this->pendingToolCalls[$tool['call_id']] = $tool['name'];
        }

        return $mapper;
    }

    private function mapTools(array $tools): array
    {
        return array_map(function ($tool) {
            return [
                'type' => $tool['type'] ?? 'function',
                'function' => [
                    'name' => $tool['name'] ?? '',
                    'description' => $tool['description'] ?? '',
                    'parameters' => $tool['parameters'] ?? [],
                ],
            ];
        }, $tools);
    }
}
