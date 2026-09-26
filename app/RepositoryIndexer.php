<?php

class RepositoryIndexer
{
    public function __construct(
        private readonly string $workspacePath,
    ) {
    }

    public function getDocuments(): array
    {
        $results = [];

        $realBaseDir = realpath($this->workspacePath);

        if ($realBaseDir === false || !is_dir($realBaseDir)) {
            throw new RuntimeException(
                'Workspace directory does not exist: ' . $this->workspacePath
            );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realBaseDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();

                $relativePath = ltrim(substr($filePath, strlen($realBaseDir)), DIRECTORY_SEPARATOR);

                $results[] = [
                    'file'    => $relativePath,
                    'content' => file_get_contents($filePath),
                ];
            }
        }

        return $results;
    }
}
