<?php

namespace App\LLM\Ollama;

use App\LLM\DTO\LLMResponse;

class OllamaResponseMapper
{
    private int $index = 1;

    public function map(array $response): LLMResponse
    {
        return new LLMResponse(
            $response['message']['content'] ?? null,
            $this->findToolCalls($response),
            $response["prompt_eval_count"] ?? 0,
            $response["eval_count"] ?? 0,
            0
        );
    }

    private function findToolCalls(array $data): array
    {
        $toolCalls = [];

        foreach ($data['message']['tool_calls'] ?? [] as $item) {
            $toolCalls[] = [
                'type' => 'function_call',
                'name' => $item['function']['name'],
                'arguments' => json_encode(
                    $item['function']['arguments'],
                    JSON_THROW_ON_ERROR
                ),
                'call_id' => 'ollama_call_' . $this->index,
            ];

            $this->index++;
        }

        return $toolCalls;
    }
}
