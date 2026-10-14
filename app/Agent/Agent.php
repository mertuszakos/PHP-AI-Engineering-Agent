<?php

namespace App\Agent;

use App\LLM\OpenAIClient;
use App\Tools\Tool;

class Agent
{
    private const MAX_ITERATIONS = 10;

    public function __construct(
        private readonly OpenAIClient $client,
        private readonly Tool $tool
    ) {}

    private array $payload;

    private int $apiCalls = 0;
    private int $inputTokens = 0;
    private int $outputTokens = 0;
    private int $reasoningTokens = 0;

    public array $toolCalls = [];

    public function run(string $task): RunResult
    {
        $this->resetState();
        $this->createInitialPayload($task);

        $iteration = 0;

        while (true) {
            if ($iteration >= self::MAX_ITERATIONS) {
                throw new \RuntimeException(
                    'Agent reached the maximum number of iterations: ' . self::MAX_ITERATIONS
                );
            }

            $response = $this->client->createResponse($this->payload);

            $this->apiCalls++;
            $this->inputTokens += $response["usage"]["input_tokens"] ?? 0;
            $this->outputTokens += $response["usage"]["output_tokens"] ?? 0;
            $this->reasoningTokens += $response["usage"]["output_tokens_details"]["reasoning_tokens"] ?? 0;

            $toolCalls = $this->findToolCalls($response);

            if (!empty($toolCalls)) {
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

                $iteration++;

                continue;
            }

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

            throw new \RuntimeException('No answer.');
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
        try {
            $arguments = json_decode(
                $toolCall['arguments'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($arguments) || (array_is_list($arguments) && !empty($arguments))) {
                return json_encode([
                    'error' => 'Invalid tool arguments: Expected a JSON object.'
                ]);
            }
        } catch (\JsonException $e) {
            return json_encode([
                'error' => 'Invalid tool arguments JSON: ' . $e->getMessage()
            ]);
        }

        $this->toolCalls[] = [
            'name' => $toolCall['name'],
            'arguments' => $arguments,
        ];

        return match ($toolCall['name']) {
            'read_file' => (function() use ($arguments) {
                $path = $arguments['path'] ?? null;
                if (!is_string($path) || trim($path) === '') {
                    return json_encode([
                        'error' => 'Missing or invalid argument: path'
                    ]);
                }
                return $this->tool->readFile($path);
            })(),

            'list_files' => (function() use ($arguments) {
                $dir = $arguments['directory'] ?? null;
                if (!is_string($dir) || trim($dir) === '') {
                    return json_encode([
                        'error' => 'Missing or invalid argument: directory'
                    ]);
                }
                return json_encode($this->tool->listFiles($dir));
            })(),

            'search_code' => (function() use ($arguments) {
                $search = $arguments['search'] ?? null;
                if (!is_string($search) || trim($search) === '') {
                    return json_encode([
                        'error' => 'Missing or invalid argument: search'
                    ]);
                }
                return json_encode($this->tool->searchCode($search));
            })(),

            'git_diff' => $this->tool->gitDiff(),

            'semantic_search' => (function() use ($arguments) {
                $query = $arguments['query'] ?? null;
                if (!is_string($query) || trim($query) === '') {
                    return json_encode([
                        'error' => 'Missing or invalid argument: query'
                    ]);
                }
                return json_encode($this->tool->semanticSearch($query));
            })(),

            default => throw new \RuntimeException(
                'Unknown tool: ' . $toolCall['name']
            ),
        };
    }

    private function createToolResultPayload(string $responseId, array $toolOutputs): void
    {
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

    private function resetState(): void
    {
        $this->apiCalls = 0;
        $this->inputTokens = 0;
        $this->outputTokens = 0;
        $this->reasoningTokens = 0;

        $this->toolCalls = [];
    }
}
