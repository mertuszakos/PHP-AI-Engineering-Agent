<?php

class CodeChunker
{
    public function __construct(
        private readonly int $chunkSize = 100,
        private readonly int $overlap = 20,
    ) {
        if (
            $this->chunkSize <= 0 ||
            $this->overlap < 0 ||
            $this->overlap >= $this->chunkSize
        ) {
            throw new InvalidArgumentException('Invalid chunk configuration.');
        }
    }

    public function chunk(array $documents): array
    {
        $result = [];
        foreach ($documents as $document) {
            $lines = preg_split('/\R/', $document['content']);
            $offset = 0;
            $step = $this->chunkSize - $this->overlap;

            while ($offset < count($lines)) {

                $chunkLines = array_slice(
                    $lines,
                    $offset,
                    $this->chunkSize
                );

                $result[] = [
                    'file' => $document['file'],
                    'start_line' => $offset + 1,
                    'end_line' => $offset + count($chunkLines),
                    'content' => implode(PHP_EOL, $chunkLines),
                ];

                $offset += $step;
            }
        }

        return $result;
    }
}