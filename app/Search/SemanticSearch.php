<?php

namespace App\Search;

use App\LLM\EmbeddingClient;

class SemanticSearch
{
    public $index = [];

    public function __construct(
        private readonly EmbeddingClient $embeddingClient,
        private readonly VectorMath $vectorMath,
    ) {}

    public function index(array $chunks): void
    {
        foreach ($chunks as $chunk) {
            $embeddingText = sprintf(
                "File: %s\nSymbol: %s\nType: %s\n\n%s",
                $chunk['file'],
                $chunk['symbol'],
                $chunk['type'],
                $chunk['content']
            );

            $this->index[] = [
                ...$chunk,
                'vector' => $this->embeddingClient->embed($embeddingText),
            ];
        }
    }

    public function search(string $query): array
    {
        $queryVector = $this->embeddingClient->embed($query);

        $result = [];

        foreach ($this->index as $value) {
            $record = [
                ...$value,
                'similarity' => $this->vectorMath->cosineSimilarity(
                    $queryVector,
                    $value['vector']
                ),
            ];

            unset($record['vector']);

            $result[] = $record;
        }

        usort($result, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $result;
    }
}
