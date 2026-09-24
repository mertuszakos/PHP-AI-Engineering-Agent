<?php

class SemanticSearch
{
    public $index = [];

    public function __construct(
        private readonly EmbeddingClient $embeddingClient,
        private readonly VectorMath $vectorMath,
        ) {}

    public function index(array $documents): void
    {
        foreach ($documents as $document) {
            $this->index[] = [
                'file' => $document['file'],
                'content' => $document['content'],
                'vector' => $this->embeddingClient->embed($document['content']),
            ];
        }
    }

    public function search(string $query): array
    {
        $queryVector = $this->embeddingClient->embed($query);

        $result = [];

        foreach ($this->index as $value) {
            $result[] = [
                'file' => $value['file'],
                'similarity' => $this->vectorMath->cosineSimilarity(
                    $queryVector,
                    $value['vector']
                ),
            ];
        }

        usort($result, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $result;
    }
}