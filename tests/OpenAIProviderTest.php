<?php

use PHPUnit\Framework\TestCase;
use App\LLM\OpenAI\OpenAIProvider;
use App\LLM\OpenAI\OpenAIClient;
use App\LLM\OpenAI\OpenAIResponseMapper;
use App\LLM\DTO\LLMResponse;

class OpenAIProviderTest extends TestCase
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
        $client = $this->createMock(OpenAIClient::class);

        $client->expects($this->once())
        ->method('createResponse')
        ->with($this->callback(function (array $payload): bool {
            return $payload['model'] === 'gpt-5.6'
                && $payload['input'] === 'Find authentication vulnerabilities'
                && $payload['tools'] === $this->tools
                && $payload['tool_choice'] === 'auto';
        }))
        ->willReturn([
            'id' => 'resp_123',
            'output' => [],
            'usage' => [],
        ]);

        $responseMapper = new OpenAIResponseMapper();

        $openAIProvider = new OpenAIProvider($client, $responseMapper);

        $result = $openAIProvider->startConversation(
            'Find authentication vulnerabilities',
            $this->tools
        );

        $this->assertInstanceOf(LLMResponse::class, $result);
        $this->assertSame(0, $result->inputTokens);
    }

    public function testContinueConversation(): void
    {
        $client = $this->createMock(OpenAIClient::class);

        $capturedPayloads = [];

        $client->expects($this->exactly(2))
        ->method('createResponse')
        ->willReturnCallback(
            function (array $payload) use (&$capturedPayloads): array {
                $capturedPayloads[] = $payload;

                return [
                    'id' => count($capturedPayloads) === 1
                        ? 'resp_123'
                        : 'resp_456',
                    'output' => [],
                    'usage' => [],
                ];
            }
        );

        $responseMapper = new OpenAIResponseMapper();

        $openAIProvider = new OpenAIProvider($client, $responseMapper);

        $openAIProvider->startConversation(
            'Find authentication vulnerabilities',
            $this->tools
        );

        $toolResults = [
            [
                'type' => 'function_call_output',
                'call_id' => 'call_123',
                'output' => 'File contents: <?php echo "Hello";',
            ],
        ];

        $result = $openAIProvider->continueConversation($toolResults);

        $secondPayload = $capturedPayloads[1];

        $this->assertInstanceOf(LLMResponse::class, $result);
        $this->assertNull($result->text);
        $this->assertSame('resp_123', $secondPayload['previous_response_id']);
        $this->assertSame($toolResults, $secondPayload['input']);
        $this->assertSame($this->tools, $secondPayload['tools']);
    }
}