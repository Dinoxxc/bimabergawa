<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ProductImageProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Tests\TestCase;

class ProductImageProcessingServiceTest extends TestCase
{
    private ProductImageProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ProductImageProcessingService;
    }

    public function test_process_and_store_converts_uploaded_image_to_webp_with_uuid(): void
    {
        $manager = new ImageManager(new Driver);
        $tempFile = tempnam(sys_get_temp_dir(), 'img_test_');
        file_put_contents($tempFile, (string) $manager->createImage(800, 600)->encodeUsingFileExtension('png'));

        $uploadedFile = new UploadedFile(
            $tempFile,
            'sample-catalog.png',
            'image/png',
            null,
            true
        );

        $storedPath = $this->service->processAndStore($uploadedFile, 'catalog');

        $this->assertStringStartsWith('catalog/', $storedPath);
        $this->assertStringEndsWith('.webp', $storedPath);
        $this->assertTrue(Storage::disk('public')->exists($storedPath));

        $storedBytes = Storage::disk('public')->get($storedPath);
        $decoded = $manager->decode($storedBytes);
        $this->assertSame(800, $decoded->width());
        $this->assertSame(600, $decoded->height());

        @unlink($tempFile);
    }

    public function test_process_and_store_scales_down_large_images_exceeding_1600px(): void
    {
        $manager = new ImageManager(new Driver);
        $tempFile = tempnam(sys_get_temp_dir(), 'img_large_');
        file_put_contents($tempFile, (string) $manager->createImage(2400, 1200)->encodeUsingFileExtension('jpeg'));

        $uploadedFile = new UploadedFile(
            $tempFile,
            'giant-photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $result = $this->service->processAndStoreWithDimensions($uploadedFile, 'catalog', maxWidth: 1600);

        $this->assertSame(1600, $result['width']);
        $this->assertSame(800, $result['height']);
        $this->assertSame('image/webp', $result['mime_type']);
        $this->assertGreaterThan(0, $result['size']);
        $this->assertTrue(Storage::disk('public')->exists($result['path']));

        @unlink($tempFile);
    }

    public function test_process_and_store_from_file_path_string(): void
    {
        $manager = new ImageManager(new Driver);
        $tempFile = tempnam(sys_get_temp_dir(), 'img_string_');
        file_put_contents($tempFile, (string) $manager->createImage(500, 500)->encodeUsingFileExtension('png'));

        $path = $this->service->processAndStore($tempFile, 'products');

        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));

        @unlink($tempFile);
    }

    public function test_delete_if_exists_removes_file_safely(): void
    {
        Storage::disk('public')->put('catalog/to-delete.webp', 'dummy-data');
        $this->assertTrue(Storage::disk('public')->exists('catalog/to-delete.webp'));

        $deleted = $this->service->deleteIfExists('catalog/to-delete.webp');
        $this->assertTrue($deleted);
        $this->assertFalse(Storage::disk('public')->exists('catalog/to-delete.webp'));

        $this->assertFalse($this->service->deleteIfExists(null));
        $this->assertFalse($this->service->deleteIfExists('nonexistent/file.webp'));
    }

    public function test_invalid_file_path_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Berkas gambar tidak ditemukan');

        $this->service->processAndStore('/path/to/nonexistent/image.jpg');
    }
}
