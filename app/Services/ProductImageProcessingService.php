<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use SplFileInfo;

class ProductImageProcessingService
{
    public const DEFAULT_MAX_WIDTH = 1600;

    public const DEFAULT_MAX_HEIGHT = 1600;

    public const DEFAULT_WEBP_QUALITY = 82;

    public const DEFAULT_DIRECTORY = 'catalog';

    protected ImageManager $manager;

    public function __construct(?ImageManager $manager = null)
    {
        $this->manager = $manager ?? new ImageManager(new Driver);
    }

    /**
     * Baca berkas gambar, skala proporsional (maks 1600px), konversi ke WebP murni 82%
     * (otomatis membersihkan metadata EXIF), dan simpan ke disk publik dengan penamaan UUID v4.
     */
    public function processAndStore(
        UploadedFile|string|SplFileInfo $file,
        string $directory = self::DEFAULT_DIRECTORY,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $quality = self::DEFAULT_WEBP_QUALITY
    ): string {
        $result = $this->processAndStoreWithDimensions($file, $directory, $maxWidth, $quality);

        return $result['path'];
    }

    /**
     * Proses gambar dan kembalikan array metadata lengkap (path, dimensi width/height, ukuran byte, dan mime).
     */
    public function processAndStoreWithDimensions(
        UploadedFile|string|SplFileInfo $file,
        string $directory = self::DEFAULT_DIRECTORY,
        int $maxWidth = self::DEFAULT_MAX_WIDTH,
        int $quality = self::DEFAULT_WEBP_QUALITY
    ): array {
        $image = $this->readImage($file);

        // Skala proporsional jika resolusi melebihi batas maksimum
        $image->scaleDown(width: $maxWidth, height: self::DEFAULT_MAX_HEIGHT);

        $finalWidth = $image->width();
        $finalHeight = $image->height();

        // Konversi ke WebP kualitas 82%
        $encodedWebp = $image->encodeUsingFileExtension('webp', quality: $quality);
        $encodedBytes = (string) $encodedWebp;

        $cleanDir = SecureFileUploadService::sanitizeDirectoryPath($directory);
        if ($cleanDir === '') {
            $cleanDir = self::DEFAULT_DIRECTORY;
        }

        $fileName = $cleanDir.'/'.Str::uuid()->toString().'.webp';

        Storage::disk('public')->put($fileName, $encodedBytes);

        $fileSize = strlen($encodedBytes);

        // Bersihkan memori segera untuk mematuhi batas RAM shared hosting 512 MB (Rule 3.10)
        unset($image, $encodedWebp, $encodedBytes);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        return [
            'path' => $fileName,
            'width' => $finalWidth,
            'height' => $finalHeight,
            'size' => $fileSize,
            'mime_type' => 'image/webp',
        ];
    }

    /**
     * Hapus berkas gambar lama dari penyimpanan jika tersedia.
     */
    public function deleteIfExists(?string $path, string $disk = 'public'): bool
    {
        if ($path === null || trim($path) === '') {
            return false;
        }

        $cleanPath = SecureFileUploadService::sanitizeDirectoryPath($path);
        if ($cleanPath !== '' && Storage::disk($disk)->exists($cleanPath)) {
            return Storage::disk($disk)->delete($cleanPath);
        }

        return false;
    }

    /**
     * Baca gambar dari UploadedFile, path string lokal, atau SplFileInfo.
     */
    protected function readImage(UploadedFile|string|SplFileInfo $file): Image
    {
        if ($file instanceof UploadedFile) {
            $realPath = $file->getRealPath();
            if ($realPath === false || ! file_exists($realPath)) {
                throw new InvalidArgumentException('Berkas unggahan tidak memiliki path fisik yang valid.');
            }

            return $this->manager->decodePath($realPath);
        }

        if ($file instanceof SplFileInfo) {
            $realPath = $file->getRealPath();
            if ($realPath === false || ! file_exists($realPath)) {
                throw new InvalidArgumentException('Objek berkas SplFileInfo tidak ditemukan pada filesystem.');
            }

            return $this->manager->decodePath($realPath);
        }

        if (is_string($file)) {
            if (file_exists($file)) {
                return $this->manager->decodePath($file);
            }

            // Coba baca dari disk public / storage
            if (Storage::disk('public')->exists($file)) {
                return $this->manager->decode(Storage::disk('public')->get($file));
            }

            throw new InvalidArgumentException(sprintf('Berkas gambar tidak ditemukan pada path: [%s].', $file));
        }

        throw new InvalidArgumentException('Tipe berkas masukan tidak didukung untuk pemrosesan gambar.');
    }
}
