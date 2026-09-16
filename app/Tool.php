<?php

class Tool
{
    public $toolsList = [
        [
            'type' => 'function',
            'name' => 'read_file',
            'description' => 'Read the contents of a file from the project.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Relative path of the file to read.',
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
            'description' => 'Read the path directory.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'directory' => [
                        'type' => 'string',
                        'description' => 'Relative directory of to list.',
                    ],
                ],
                'required' => ['directory'],
                'additionalProperties' => false,
            ],
            'strict' => true,
        ],
    ];

    public function readFile(string $path): string
    {
        if (!str_starts_with($path, 'workspace/')) {
            throw new RuntimeException('Permission denied');
        }

        $file = file_get_contents($path);

        if ($file === false) {
            throw new RuntimeException('File not found');
        }

        return $file;
    }

    public function listFiles(string $directory): array
    {
        if ($directory !== 'workspace' && !str_starts_with($directory, 'workspace/')) {
            throw new RuntimeException('Permission denied');
        }

        return array_diff(
            scandir($directory),
            ['.', '..']
        );
    } 
}