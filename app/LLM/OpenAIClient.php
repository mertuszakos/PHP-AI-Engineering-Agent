<?php

namespace App\LLM;

class OpenAIClient implements LLMProviderInterface
{
    public function __construct(private readonly string $apiKey) {}

    public function createResponse(array $payload): array
    {
        $ch = curl_init('https://api.openai.com/v1/responses');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException(curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new \RuntimeException(
                'OpenAI API error: ' . $response
            );
        }

        return $data;
    }
}