<?php

use PHPUnit\Framework\TestCase;
use App\LLM\Ollama\OllamaResponseMapper;

class OllamaResponseMapperTest extends TestCase
{
    private OllamaResponseMapper $responseMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responseMapper = new OllamaResponseMapper();
    }

    public function testMessageResponse(): void
    {
        $response = [
            'message' => [
                'role' => 'assistant',
                'content' => 'Hello from Qwen!',
            ],
            'prompt_eval_count' => 25,
            'eval_count' => 15,
        ];

        $result = $this->responseMapper->map($response);

        $this->assertSame('Hello from Qwen!', $result->text);
        $this->assertSame([], $result->toolCalls);
        $this->assertSame(25, $result->inputTokens);
        $this->assertSame(15, $result->outputTokens);
        $this->assertSame(0, $result->reasoningTokens);
    }

    public function testEmptyResponse(): void
    {
        $result = $this->responseMapper->map([]);

        $this->assertNull($result->text);
        $this->assertSame([], $result->toolCalls);
        $this->assertSame(0, $result->inputTokens);
        $this->assertSame(0, $result->outputTokens);
        $this->assertSame(0, $result->reasoningTokens);
    }

    public function testEmptyContent(): void
    {
        $response = [
            'message' => [
                'role' => 'assistant',
                'content' => '',
            ],
        ];

        $result = $this->responseMapper->map($response);

        $this->assertSame('', $result->text);
    }

    public function testToolCallResponse(): void
    {
        $response = [
            'message' => [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => [
                    [
                        'function' => [
                            'name' => 'read_file',
                            'arguments' => [
                                'path' => 'src/AuthService.php',
                            ],
                        ],
                    ],
                ],
            ],
            'prompt_eval_count' => 50,
            'eval_count' => 20,
        ];

        $result = $this->responseMapper->map($response);

        $this->assertSame('', $result->text);
        $this->assertCount(1, $result->toolCalls);
        $this->assertSame('read_file', $result->toolCalls[0]['name']);

        $jsonString = $result->toolCalls[0]['arguments'];
        $arguments = json_decode($jsonString, true);
        $this->assertIsArray($arguments, 'Az argumentumok nem érvényes JSON sztringek.');

        $this->assertArrayHasKey('path', $arguments);
        $this->assertEquals('src/AuthService.php', $arguments['path']);

        $this->assertNotEmpty($result->toolCalls[0]['call_id']);
        $this->assertSame(50, $result->inputTokens);
        $this->assertSame(20, $result->outputTokens);
    }

    public function testTwoToolCallResponse(): void
    {
        $response = [
            'message' => [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => [
                    [
                        'function' => [
                            'name' => 'read_file',
                            'arguments' => [
                                'path' => 'src/AuthService.php',
                            ],
                        ],
                    ],
                    [
                        'function' => [
                            'name' => 'list_files',
                            'arguments' => [
                                'path' => 'src/AuthService.php',
                            ],
                        ],
                    ],
                ],
            ],
            'prompt_eval_count' => 50,
            'eval_count' => 20,
        ];

        $result = $this->responseMapper->map($response);

        $this->assertCount(2, $result->toolCalls);

        $this->assertSame('ollama_call_1', $result->toolCalls[0]['call_id']);
        $this->assertSame('ollama_call_2', $result->toolCalls[1]['call_id']);
    }
}
