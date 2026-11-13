<?php

namespace App\LLM\Ollama;

class OllamaClient
{
    public function __construct(private readonly string $baseUrl) {}

    public function chat(array $payload): array
    {
        $url = rtrim($this->baseUrl, '/') . '/api/chat';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new \RuntimeException($error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException(
                "Ollama API HTTP {$httpCode}: {$response}"
            );
        }

        return $data;
    }
}