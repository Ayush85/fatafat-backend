<?php

declare(strict_types=1);

namespace App\Services;



use App\Models\FileModel;
use App\Models\FileUsageModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FileUploadService
{
    private string $defaultDisk;

    public function __construct()
    {
        $this->defaultDisk = config('filesystems.default');
    }

    public function upload(
        UploadedFile $file,
        string $folder,
        array $meta = []
    ): FileModel {
        $folder = trim($folder, '/');

        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = Str::slug($baseName) ?: 'file';

        $finalName = $this->generateUniqueFileName($baseName, $extension);

        $path = $folder . '/' . $finalName;

        Storage::disk($this->defaultDisk)->putFileAs($folder, $file, $finalName);


        $dimensions = $this->getImageDimensions($file);

        return FileModel::create([
            'key' => (string) Str::uuid(),
            'file_name' => $finalName,
            'file_path' => $path,
            'extension' => $extension,
            'seq_no' => 1,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'height' => $dimensions['height'],
            'width' => $dimensions['width'],
            'disk' => $this->defaultDisk,
            'meta' => !empty($meta) ? $meta : null,
        ]);
    }

    public function uploadWithUsage(
        UploadedFile $file,
        string $folder,
        string $usageType,
        int|string $usageId,
        ?string $title = null,
        ?string $altText = null,
        array $fileMeta = [],
        array $usageMeta = []
    ): FileModel {
        $storedFile = $this->upload($file, $folder, $fileMeta);

        FileUsageModel::create([
            'file_id' => $storedFile->id,
            'usage_type' => $usageType,
            'usage_id' => $usageId,
            'title' => $title,
            'alt_text' => $altText,
            'meta' => !empty($usageMeta) ? $usageMeta : null,
        ]);

        return $storedFile;
    }

    public function uploadForModel(
        UploadedFile $file,
        string $folder,
        Model $model,
        ?string $title = null,
        ?string $altText = null,
        array $fileMeta = [],
        array $usageMeta = []
    ): FileModel {
        return $this->uploadWithUsage(
            file: $file,
            folder: $folder,
            usageType: $model::class,
            usageId: $model->getKey(),
            title: $title,
            altText: $altText,
            fileMeta: $fileMeta,
            usageMeta: $usageMeta
        );
    }

    public function uploadSignature(
        UploadedFile|string|null $signature,
        string $folder,
        array $meta = []
    ): ?FileModel {
        if (empty($signature)) {
            return null;
        }

        if ($signature instanceof UploadedFile) {
            return $this->upload(
                file: $signature,
                folder: $folder,
                meta: $meta
            );
        }

        if (is_string($signature) && preg_match('/^data:(.*?);base64,(.*)$/', $signature, $matches)) {
            $folder = trim($folder, '/');

            $mimeType = $matches[1];
            $encodedData = $matches[2];
            $binaryData = base64_decode($encodedData, true);

            if ($binaryData === false) {
                throw new InvalidArgumentException('Invalid base64 signature data.');
            }

            $extension = match ($mimeType) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'image/svg+xml' => 'svg',
                default => 'png',
            };

            $baseName = 'signature';
            $finalName = $this->generateUniqueFileName($baseName, $extension);

            $path = $folder . '/' . $finalName;

            Storage::disk($this->defaultDisk)->put($path, $binaryData);

            $dimensions = @getimagesizefromstring($binaryData);

            return FileModel::create([
                'key' => (string) Str::uuid(),
                'file_name' => $finalName,
                'file_path' => $path,
                'extension' => $extension,
                'seq_no' => 1,
                'mime_type' => $mimeType,
                'file_size' => strlen($binaryData),
                'height' => $dimensions[1] ?? null,
                'width' => $dimensions[0] ?? null,
                'disk' => $this->defaultDisk,
                'content_hash' => hash('sha256', $binaryData),
                'meta' => !empty($meta) ? $meta : null,
            ]);
        }

        throw new InvalidArgumentException(
            'Signature must be either an uploaded file or a valid base64 image.'
        );
    }

    public function uploadSignatureWithUsage(
        UploadedFile|string|null $signature,
        string $folder,
        string $usageType,
        int|string $usageId,
        ?string $title = null,
        ?string $altText = null,
        array $fileMeta = [],
        array $usageMeta = []
    ): ?FileModel {
        $storedFile = $this->uploadSignature(
            signature: $signature,
            folder: $folder,
            meta: $fileMeta
        );

        if (!$storedFile) {
            return null;
        }

        FileUsageModel::create([
            'file_id' => $storedFile->id,
            'usage_type' => $usageType,
            'usage_id' => $usageId,
            'title' => $title,
            'alt_text' => $altText,
            'meta' => !empty($usageMeta) ? $usageMeta : null,
        ]);

        return $storedFile;
    }

    public function uploadSignatureForModel(
        UploadedFile|string|null $signature,
        string $folder,
        Model $model,
        ?string $title = null,
        ?string $altText = null,
        array $fileMeta = [],
        array $usageMeta = []
    ): ?FileModel {
        return $this->uploadSignatureWithUsage(
            signature: $signature,
            folder: $folder,
            usageType: $model->getTable(),
            usageId: $model->getKey(),
            title: $title,
            altText: $altText,
            fileMeta: $fileMeta,
            usageMeta: $usageMeta
        );
    }

    public function delete(FileModel $file): bool
    {
        return Storage::disk($file->disk)->delete($file->file_path);
    }

    private function getImageDimensions(UploadedFile $file): array
    {
        $mime = (string) $file->getMimeType();

        if (!str_starts_with($mime, 'image/')) {
            return ['width' => null, 'height' => null];
        }

        $size = @getimagesize($file->getRealPath());

        if (!$size) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    /**
     * Ensure the file_name column (globally unique) does not collide.
     */
    private function generateUniqueFileName(string $baseName, string $extension): string
    {
        $extension = ltrim($extension, '.');
        $extPart = $extension !== '' ? '.' . $extension : '';

        $candidate = $baseName . $extPart;

        // Use high-entropy suffix when a collision is detected. Loop guards against rare race conditions.
        while (FileModel::where('file_name', $candidate)->exists()) {
            $suffix = now()->format('YmdHisv') . '_' . Str::random(4);
            $candidate = $baseName . '_' . $suffix . $extPart;
        }

        return $candidate;
    }
}
