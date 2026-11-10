<?php

use PHPUnit\Framework\TestCase;
use App\LLM\OllamaClient;
use App\LLM\OllamaProvider;
use App\LLM\OllamaResponseMapper;
use App\LLM\LLMResponse;

class OllamaProviderTest extends TestCase
{
    private array $tools;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tools = [
            [
                'type' => 'function',
                'name' => 'read_file',
                'description' => 'Read a file from the repository',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => ['type' => 'string'],
                    ],
                    'required' => ['path'],
                ],
            ],
        ];
    }

    public function testStartConversation(): void
    {
        $client = $this->createMock(OllamaClient::class);

        $client->expects($this->once())
            ->method('chat')
            ->with($this->callback(function (array $payload): bool {
                return $payload['model'] === 'qwen3.5:9b'
                    && $payload['messages'][0]["content"] === 'Explain dependency injection in PHP in a few words.'
                    && $payload['stream'] === false
                    && $payload['think'] === false
                    && $payload['messages'][0]['role'] === 'user';
            }))
            ->willReturn([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Hello from Qwen!',
                ],
                'prompt_eval_count' => 20,
                'eval_count' => 10,
            ]);

        $responseMapper = new OllamaResponseMapper();

        $ollamaAIProvider = new OllamaProvider($client, $responseMapper);

        $result = $ollamaAIProvider->startConversation(
            'Explain dependency injection in PHP in a few words.',
            []
        );

        $this->assertInstanceOf(LLMResponse::class, $result);
        $this->assertSame('Hello from Qwen!', $result->text);
        $this->assertSame(20, $result->inputTokens);
        $this->assertSame(10, $result->outputTokens);
        $this->assertSame(0, $result->reasoningTokens);
    }

    public function testStartConversationWithTools(): void
    {
        $client = $this->createMock(OllamaClient::class);

        $client->expects($this->once())
            ->method('chat')
            ->with($this->callback(function (array $payload): bool {
                return $payload['tools'][0]['type'] === 'function'
                    && $payload['tools'][0]['function']['name'] === 'read_file'
                    && $payload['tools'][0]['function']['parameters']['required'] === ['path'];
            }))
            ->willReturn([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Hello from Qwen!',
                ],
                'prompt_eval_count' => 20,
                'eval_count' => 10,
            ]);

        $responseMapper = new OllamaResponseMapper();

        $ollamaAIProvider = new OllamaProvider($client, $responseMapper);

        $result = $ollamaAIProvider->startConversation(
            'Explain dependency injection in PHP in a few words.',
            $this->tools
        );

        $this->assertInstanceOf(LLMResponse::class, $result);
    }

    public function testContinueConversation(): void
    {
        $client = $this->createMock(OllamaClient::class);

        $capturedPayloads = [];
        $callIndex = 0;

        $client->expects($this->exactly(2))
            ->method('chat')
            ->willReturnCallback(
                function (array $payload) use (&$capturedPayloads, &$callIndex): array {
                    $capturedPayloads[] = $payload;
                    $callIndex++;

                    if ($callIndex === 1) {
                        // Az első Ollama-válasz: toolhívás
                        return [
                            'message' => [
                                'role' => 'assistant',
                                'content' => '',
                                'tool_calls' => [
                                    [
                                        'function' => [
                                            'name' => 'read_file',
                                            'arguments' => [
                                                'path' => 'AuthService.php',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ];
                    }

                    // A második Ollama-válasz: végső szöveg
                    return [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'The authentication method has a security issue.',
                        ],
                    ];
                }
            );

        $responseMapper = new OllamaResponseMapper();

        $ollamaProvider = new OllamaProvider($client, $responseMapper);

        $ollamaProvider->startConversation(
            'Find authentication vulnerabilities',
            $this->tools
        );

        $toolResults = [
            [
                'call_id' => 'ollama_call_1',
                'output' => 'File contents: <?php echo "Hello";',
            ],
        ];

        $result = $ollamaProvider->continueConversation($toolResults);

        $secondPayload = $capturedPayloads[1];

        $this->assertInstanceOf(LLMResponse::class, $result);
        $this->assertSame(
            'The authentication method has a security issue.',
            $result->text
        );
        $this->assertCount(3, $secondPayload['messages']);
        $this->assertSame(
            'user',
            $secondPayload['messages'][0]['role']
        );
        $this->assertSame(
            'assistant',
            $secondPayload['messages'][1]['role']
        );
        $this->assertSame(
            'read_file',
            $secondPayload['messages'][1]['tool_calls'][0]['function']['name']
        );
        $this->assertSame(
            'tool',
            $secondPayload['messages'][2]['role']
        );
        $this->assertSame(
            'read_file',
            $secondPayload['messages'][2]['tool_name']
        );
        $this->assertSame(
            'File contents: <?php echo "Hello";',
            $secondPayload['messages'][2]['content']
        );
    }
}
