<?php

namespace App\LLM;

class EmbeddingClient
{
    public function __construct(private readonly string $apiKey) {}

    public function embed(string $text): array
    {
        $ch = curl_init('https://api.openai.com/v1/embeddings');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                "input" => $text,
                "model" => "text-embedding-3-small"
            ]),
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

        $answer = $data['data'][0]['embedding'] ?? null;

        if (!is_array($answer) || empty($answer)) {
            throw new \RuntimeException(
                'Embedding API returned an invalid vector.'
            );
        }

        foreach ($answer as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw new \RuntimeException(
                    'Embedding vector contains non-numeric values.'
                );
            }

            if (!is_finite((float) $value)) {
                throw new \RuntimeException(
                    'Embedding vector contains non-finite values.'
                );
            }
        }

        return $answer;
    }
}
