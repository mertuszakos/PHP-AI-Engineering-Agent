<?php

class EmbeddingClient
{
    public function __construct(private readonly string $apiKey) {}

    public function embed(string $text): ?array
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
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new RuntimeException(curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new RuntimeException(
                'OpenAI API error: ' . $response
            );
        }

        return $data['data'][0]['embedding'] ?? null;
    }
}
