<?php

class Agent
{
    public function __construct(
        private readonly OpenAIClient $client,
        private readonly Tool $tool
    ) {
    }

    private $payload;

    private int $apiCalls = 0;
    private int $inputTokens = 0;
    private int $outputTokens = 0;
    private int $reasoningTokens = 0;

    public function run(string $task): string
    {
        $this->createInitialPayload($task);

        while (true) {
            $response = $this->client->createResponse($this->payload);

            $this->collectUsage($response);

            $finalText = $this->findFinalText($response);

            if ($finalText !== null) {
                $this->printMetrics();

                return $finalText;
            }

            $toolCalls = $this->findToolCalls($response);

            $toolOutputs = [];

            foreach ($toolCalls as $toolCall) {
                $toolResult = $this->executeTool($toolCall);

                $toolOutputs[] = [
                    'type' => 'function_call_output',
                    'call_id' => $toolCall['call_id'],
                    'output' => $toolResult,
                ];
            }

            $this->createToolResultPayload(
                $response['id'],
                $toolOutputs
            );
        }
    }

    private function createInitialPayload(string $task): void
    {
        $this->payload = [
            'model' => 'gpt-5.6',
            'reasoning' => [
                'effort' => 'low',
            ],
            'tools' => $this->tool->toolsList,
            'tool_choice' => 'auto',
            'input' => $task,
        ];
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

    private function executeTool(array $toolCall): string
    {
        echo sprintf(
            "Tool call: %s %s\n",
            $toolCall['name'],
            $toolCall['arguments']
        );

        $arguments = json_decode(
            $toolCall['arguments'],
            true
        );

        return match ($toolCall['name']) {
            'read_file' => $this->tool->readFile(
                $arguments['path']
            ),

            'list_files' => json_encode(
                $this->tool->listFiles(
                    $arguments['directory']
                )
            ),

            'search_code' => json_encode(
                $this->tool->searchCode(
                    $arguments['search']
                )
            ),

            default => throw new RuntimeException(
                'Unknown tool: ' . $toolCall['name']
            ),
        };
    }

    private function createToolResultPayload(
        string $responseId,
        array $toolOutputs
    ): void {
        $this->payload = [
            'model' => 'gpt-5.6',
            'reasoning' => [
                'effort' => 'low',
            ],
            'tools' => $this->tool->toolsList,
            'previous_response_id' => $responseId,
            'input' => $toolOutputs,
        ];
    }

    private function collectUsage(array $response): void
    {
        $this->apiCalls++;
        $this->inputTokens += $response["usage"]["input_tokens"] ?? 0;
        $this->outputTokens += $response["usage"]["output_tokens"] ?? 0;
        $this->reasoningTokens += $response["usage"]["output_tokens_details"]["reasoning_tokens"] ?? 0;
    }

    private function printMetrics(): void
    {
        $totalTokens = $this->inputTokens + $this->outputTokens;

        echo "--- Run metrics ---\n";
        echo "API calls: {$this->apiCalls}\n";
        echo "Input tokens: {$this->inputTokens}\n";
        echo "Output tokens: {$this->outputTokens}\n";
        echo "Reasoning tokens: {$this->reasoningTokens}\n";
        echo "Total tokens: {$totalTokens}\n";
    }
}