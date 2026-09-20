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

    public array $toolCalls = [];

    public function run(string $task): RunResult
    {
        $this->createInitialPayload($task);

        while (true) {
            $response = $this->client->createResponse($this->payload);

            $this->apiCalls++;
            $this->inputTokens += $response["usage"]["input_tokens"] ?? 0;
            $this->outputTokens += $response["usage"]["output_tokens"] ?? 0;
            $this->reasoningTokens += $response["usage"]["output_tokens_details"]["reasoning_tokens"] ?? 0;

            $finalText = $this->findFinalText($response);

            if ($finalText !== null) {
                return new RunResult(
                    $finalText,
                    $this->toolCalls,
                    $this->apiCalls,
                    $this->inputTokens,
                    $this->outputTokens,
                    $this->reasoningTokens
                );
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
        $arguments = json_decode(
            $toolCall['arguments'],
            true
        );

        $this->toolCalls[] = [
            'name' => $toolCall['name'],
            'arguments' => $arguments,
        ];

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

            'git_diff' => $this->tool->gitDiff(),

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
}