<?php

namespace Tests\Unit;

use App\Support\ImageStorage;
use Tests\TestCase;

/**
 * Gambar (foto scan, GradCAM, avatar) harus selalu berada di disk yang bisa
 * diserve publik lewat /storage/... — disk `local` (private) menolak akses
 * tanpa signature sehingga404 di production.
 */
class ImageStorageDiskTest extends TestCase
{
    public function test_default_local_disk_is_escalated_to_public(): void
    {
        config(['ngingu.upload.disk' => null, 'filesystems.default' => 'local']);

        $this->assertSame('public', app(ImageStorage::class)->disk());
    }

    public function test_explicit_local_upload_disk_is_escalated_to_public(): void
    {
        config(['ngingu.upload.disk' => 'local']);

        $this->assertSame('public', app(ImageStorage::class)->disk());
    }

    public function test_public_and_s3_disks_are_kept(): void
    {
        config(['ngingu.upload.disk' => null, 'filesystems.default' => 'public']);
        $this->assertSame('public', app(ImageStorage::class)->disk());

        config(['ngingu.upload.disk' => null, 'filesystems.default' => 's3']);
        $this->assertSame('s3', app(ImageStorage::class)->disk());

        config(['ngingu.upload.disk' => 's3']);
        $this->assertSame('s3', app(ImageStorage::class)->disk());
    }
}
