<?php

namespace App\Agent;

use App\LLM\ConversationProviderInterface;
use App\Tools\Tool;

class Agent
{
    private const MAX_ITERATIONS = 10;

    public function __construct(
        private readonly ConversationProviderInterface $provider,
        private readonly Tool $tool
    ) {}

    private int $apiCalls = 0;
    private int $inputTokens = 0;
    private int $outputTokens = 0;
    private int $reasoningTokens = 0;

    public array $toolCalls = [];

    public function run(string $task): RunResult
    {
        $this->resetState();

        $iteration = 1;

        $response = $this->provider->startConversation(
            $task,
            $this->tool->toolsList
        );

        while (true) {
            $this->apiCalls++;
            $this->inputTokens += $response->inputTokens;
            $this->outputTokens += $response->outputTokens;
            $this->reasoningTokens += $response->reasoningTokens;

            $toolCalls = $response->toolCalls;

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

                if ($iteration >= self::MAX_ITERATIONS) {
                    throw new \RuntimeException(
                        'Agent reached the maximum number of iterations: ' . self::MAX_ITERATIONS
                    );
                }

                $response = $this->provider->continueConversation($toolOutputs);

                $iteration++;

                continue;
            }

            $finalText = $response->text;

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

    private function resetState(): void
    {
        $this->apiCalls = 0;
        $this->inputTokens = 0;
        $this->outputTokens = 0;
        $this->reasoningTokens = 0;

        $this->toolCalls = [];
    }
}
