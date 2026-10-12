<?php

namespace App;

class RepositorySemanticIndexer
{
    public function __construct(
        private readonly RepositoryIndexer $repositoryIndexer,
        private readonly PhpSemanticChunker $chunker
    ) {}

    public function index(): array
    {
        $return = [];
        $files = $this->repositoryIndexer->getDocuments();

        foreach ($files as $file) {
            $chunks = $this->chunker->chunk($file["content"]);

            foreach ($chunks as $chunk) {
                $chunk["file"] = $file["file"];
                $return[] = $chunk;
            }
        }

        return $return;
    }
}
