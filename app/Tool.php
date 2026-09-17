<?php

class Tool
{
    public $toolsList = [
        [
            'type' => 'function',
            'name' => 'read_file',
            'description' =>
                'Read a file from the mounted project workspace.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' =>
                            'File path relative to the project root.',
                    ],
                ],
                'required' => ['path'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ],
        [
            'type' => 'function',
            'name' => 'list_files',
            'description' =>
                'List files in a directory of the mounted project workspace. '
                . 'Use "." to list the project root.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'directory' => [
                        'type' => 'string',
                        'description' =>
                            'Path relative to the project root. Use "." for the project root.',
                    ],
                ],
                'required' => ['directory'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ],
        [
            'type' => 'function',
            'name' => 'search_code',
            'description' =>
                'Search recursively through the entire mounted workspace '
                . 'for the given text and return matching file paths.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'search' => [
                        'type' => 'string',
                        'description' => 'The word being searched for.',
                    ],
                ],
                'required' => ['search'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ],
    ];

    public function readFile(string $path): string
    {
        $path = $this->resolvePath($path);

        $file = file_get_contents($path);

        if ($file === false) {
            throw new RuntimeException('File not found');
        }

        return $file;
    }

    public function listFiles(string $directory): array
    {
        $directory = $this->resolvePath($directory);

        return array_diff(
            scandir($directory),
            ['.', '..']
        );
    }

    public function searchCode(string $needle): array
    {
        $baseDir = $this->resolvePath('.');
        $results = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $handle = fopen($file->getPathname(), "r");
                if (!$handle) continue;

                while (($line = fgets($handle)) !== false) {
                    if (stripos($line, $needle) !== false) {
                        $results[] = $file->getPathname();
                        break;
                    }
                }

                fclose($handle);
            }
        }

        return $results;
    }

    private function resolvePath(string $path): string
    {
        $base = realpath('/var/www/workspace');

        if ($base === false) {
            throw new RuntimeException('Workspace not found');
        }

        $path = ltrim($path, '/');

        $full = $base . '/' . $path;

        $resolved = realpath($full);
        if ($resolved === false) {
            throw new RuntimeException('DENIED');
        }

        if (
            $resolved !== $base &&
            !str_starts_with($resolved, $base . DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('DENIED');
        }

        return $resolved;
    }
}