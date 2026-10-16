<?php

use PHPUnit\Framework\TestCase;
use App\LLM\OpenAIResponseMapper;

class OpenAIResponseMapperTest extends TestCase
{
    private OpenAIResponseMapper $responseMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responseMapper = new OpenAIResponseMapper();
    }

    public function testTextResponse(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'message',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => 'Hello!'
                        ]
                    ]
                ]
            ],
            'usage' => [
                'input_tokens' => 100,
                'output_tokens' => 25,
            ]
        ];

        $result = $this->responseMapper->map($response);

        $this->assertSame('Hello!', $result->text);
        $this->assertSame([], $result->toolCalls);
        $this->assertSame(100, $result->inputTokens);
        $this->assertSame(25, $result->outputTokens);
        $this->assertSame(0, $result->reasoningTokens);
    }

    public function testToolCallResponse(): void
    {
        $response = [
            'output' => [
                [
                    'type' => 'function_call',
                    'call_id' => 'call_123',
                    'name' => 'read_file',
                    'arguments' => '{"path":"AuthService.php"}',
                ]
            ]
        ];

        $result = $this->responseMapper->map($response);

        $this->assertNull($result->text);
        $this->assertCount(1, $result->toolCalls);
        $this->assertSame('call_123', $result->toolCalls[0]['call_id']);
        $this->assertSame('read_file', $result->toolCalls[0]['name']);
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
}