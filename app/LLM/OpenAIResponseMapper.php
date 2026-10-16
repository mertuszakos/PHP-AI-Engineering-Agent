<?php

namespace App\LLM;

class OpenAIResponseMapper
{
    public function map(array $response): LLMResponse
    {
        return new LLMResponse(
            $this->findFinalText($response),
            $this->findToolCalls($response),
            $response["usage"]["input_tokens"] ?? 0,
            $response["usage"]["output_tokens"] ?? 0,
            $response["usage"]["output_tokens_details"]["reasoning_tokens"] ?? 0
        );
    }

    private function findFinalText(array $data): ?string
    {
        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? null) === 'message') {
                foreach ($item['content'] ?? [] as $content) {
                    if (($content['type'] ?? null) === 'output_text') {
                        return $content['text'];
                    }
                }
            }
        }

        return null;
    }

    private function findToolCalls(array $data): array
    {
        $toolCalls = [];

        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? null) === 'function_call') {
                $toolCalls[] = $item;
            }
        }

        return $toolCalls;
    }
}
