<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class DailyUploadsService
{
    public function __construct(private readonly FileUploadService $fileUploadService) {}

    /**
     * Upload a file under {directory}/{Y-m-d}/ (or uploads/{Y-m-d} when directory is empty)
     * and return minimal identifiers.
     *
     * @return array{key:string,file_path:string,url:?string}
     */
    public function uploadToDailyUploads(UploadedFile $file, array $meta = [], ?string $directory = null): array
    {
        $baseDir = $this->sanitizeDirectory($directory) ?: 'uploads';
        $folder = $baseDir . '/' . now()->toDateString();

        $stored = $this->fileUploadService->upload($file, $folder, $meta);

        return [
            'key' => (string) $stored->key,
            'file_path' => (string) $stored->file_path,
            'url' => $stored->url,
        ];
    }

    /**
     * Upload multiple files under {directory}/{Y-m-d}/ (or uploads/{Y-m-d} when directory is empty)
     * and return minimal identifiers.
     *
     * @param UploadedFile[] $files
     * @return array{files:array<int,array{key:string,file_path:string,url:?string}>}
     */
    public function uploadManyToDailyUploads(array $files, array $meta = [], ?string $directory = null): array
    {
        $out = [];

        foreach ($files as $file) {
            if (!($file instanceof UploadedFile)) {
                throw new InvalidArgumentException('All files must be instances of UploadedFile.');
            }

            $out[] = $this->uploadToDailyUploads($file, $meta, $directory);
        }

        return ['files' => $out];
    }

    /**
     * Normalize and validate a user-provided base directory.
     * Returns a trimmed relative path like "docs" or "docs/sub", or null when empty.
     */
    private function sanitizeDirectory(?string $directory): ?string
    {
        if (!is_string($directory)) {
            return null;
        }

        $directory = trim($directory);
        if ($directory === '') {
            return null;
        }

        $directory = trim($directory, '/');

        // Disallow traversal and weird segments.
        if (str_contains($directory, '..') || str_contains($directory, '\\')) {
            throw new InvalidArgumentException('Invalid directory.');
        }

        // Restrict to a conservative character set to avoid surprises.
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\\/_-]*$/', $directory)) {
            throw new InvalidArgumentException('Invalid directory.');
        }

        return $directory;
    }
}

