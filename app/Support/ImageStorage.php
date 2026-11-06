<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penyimpanan gambar scan: kompresi ≤ 500 KB (NFR-01.7) + thumbnail.
 */
class ImageStorage
{
    private const MAX_BYTES = 500 * 1024;

    private const MAX_EDGE = 1600;

    private const THUMB_EDGE = 400;

    public const ALLOWED_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * Simpan satu gambar upload → ['s3_key', 's3_url', 'thumb_url'].
     */
    public function storeScanImage(UploadedFile $file, string $scanId, int $index): array
    {
        $ext = self::ALLOWED_MIME[$file->getMimeType() ?? ''] ?? 'jpg';
        $key = sprintf('scans/%s/%d-%s.%s', $scanId, $index, Str::lower(Str::random(6)), $ext);
        $thumbKey = $this->thumbKey($key);

        $disk = $this->disk();

        Storage::disk($disk)->put($key, $this->compress($file));
        Storage::disk($disk)->put($thumbKey, $this->thumbnail($file));

        return [
            's3_key' => $key,
            's3_url' => $this->url($key),
            'thumb_url' => $this->url($thumbKey),
        ];
    }

    public function delete(string $key): void
    {
        $disk = Storage::disk($this->disk());
        $disk->delete([$key, $this->thumbKey($key)]);
    }

    /**
     * Simpan foto profil (API.md §7.9) → ['s3_key', 'avatar_url'].
     *
     * @return array{s3_key: string, avatar_url: string}
     */
    public function storeAvatar(UploadedFile $file, string $userId): array
    {
        $ext = self::ALLOWED_MIME[$file->getMimeType() ?? ''] ?? 'jpg';
        $key = sprintf('avatars/%s/%s.%s', $userId, Str::lower(Str::random(10)), $ext);

        Storage::disk($this->disk())->put($key, $this->compress($file));

        return [
            's3_key' => $key,
            'avatar_url' => $this->url($key),
        ];
    }

    public function keyExists(string $key): bool
    {
        return $key !== '' && Storage::disk($this->disk())->exists($key);
    }

    /**
     * Simpan gambar ensiklopedia penyakit (FR-03.1 / FR-09.2.1) →
     * ['s3_key', 's3_url'].
     *
     * Sengaja tidak membuat thumbnail: thumbnail_url di §5.1 memakai gambar
     * utama apa adanya, dan ukuran sudah dibatasi 500 KB oleh compress().
     *
     * @return array{s3_key: string, s3_url: string}
     */
    public function storeDiseaseImage(UploadedFile $file, int|string $diseaseId): array
    {
        $ext = self::ALLOWED_MIME[$file->getMimeType() ?? ''] ?? 'jpg';
        $key = sprintf('diseases/%s/%s.%s', $diseaseId, Str::lower(Str::random(10)), $ext);

        Storage::disk($this->disk())->put($key, $this->compress($file));

        return [
            's3_key' => $key,
            's3_url' => $this->url($key),
        ];
    }

    /**
     * Simpan GradCAM hasil model (FR-10.8) → URL publik.
     *
     * GradCAM datang sebagai base64 dari ML service, tetapi kolom
     * scan_images.gradcam_url hanya varchar(255) — jadi binarnya disimpan
     * sebagai file dan hanya URL-nya yang dipertahankan.
     */
    public function storeGradcam(string $binary, ?string $scanId = null): string
    {
        $info = @getimagesizefromstring($binary);

        if ($info === false) {
            throw new \InvalidArgumentException('Bukan gambar valid');
        }

        $ext = match ($info[2] ?? 0) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };

        $key = sprintf(
            'gradcams/%s/%s.%s',
            $scanId ?? 'anon',
            Str::lower(Str::random(8)),
            $ext
        );

        Storage::disk($this->disk())->put($key, $binary);

        return $this->url($key);
    }

    public function thumbKey(string $key): string
    {
        $dir = dirname($key);
        $base = pathinfo($key, PATHINFO_FILENAME);

        return ($dir === '.' ? '' : $dir.'/').'thumb-'.$base.'.'.pathinfo($key, PATHINFO_EXTENSION);
    }

    public function url(string $key): string
    {
        return Storage::disk($this->disk())->url($key);
    }

    public function disk(): string
    {
        $disk = (string) (config('ngingu.upload.disk') ?: config('filesystems.default') ?: 'public');

        // Foto scan, GradCAM, dan avatar selalu diakses lewat URL publik
        // /storage/... tanpa signature. Disk `local` (storage/app/private) menolak
        // akses tanpa signature — ServeFile membalas 404 di production — sehingga
        // semua gambar tidak tampil. Karena itu gambar tidak pernah memakai `local`
        // (model/dataset ML tetap di sana lewat Storage::disk('local') eksplisit).
        return $disk === 'local' ? 'public' : $disk;
    }

    private function compress(UploadedFile $file): string
    {
        $original = file_get_contents($file->getRealPath());

        if (strlen($original) <= self::MAX_BYTES) {
            return $original;
        }

        $image = $this->load($file);

        if ($image === false) {
            return $original;
        }

        $image = $this->downscale($image, self::MAX_EDGE);

        $binary = $this->encode($image, (string) $file->getMimeType(), 90);

        for ($quality = 80; $quality >= 30 && strlen($binary) > self::MAX_BYTES; $quality -= 10) {
            $binary = $this->encode($image, (string) $file->getMimeType(), $quality);
        }

        imagedestroy($image);

        return strlen($binary) <= self::MAX_BYTES ? $binary : $original;
    }

    private function thumbnail(UploadedFile $file): string
    {
        $image = $this->load($file);

        if ($image === false) {
            return '';
        }

        $image = $this->downscale($image, self::THUMB_EDGE);
        $binary = $this->encode($image, 'image/jpeg', 78);
        imagedestroy($image);

        return $binary;
    }

    /** @return \GdImage|false */
    private function load(UploadedFile $file)
    {
        return match ($file->getMimeType()) {
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default => @imagecreatefromjpeg($file->getRealPath()),
        };
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function downscale($image, int $maxEdge)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxEdge && $height <= $maxEdge) {
            return $image;
        }

        $scale = min($maxEdge / $width, $maxEdge / $height);
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * @param  \GdImage  $image
     */
    private function encode($image, string $mime, int $quality): string
    {
        ob_start();

        match ($mime) {
            'image/png' => imagepng($image, null, (int) round($quality * 0.9)),
            'image/webp' => imagewebp($image, null, $quality),
            default => imagejpeg($image, null, $quality),
        };

        return (string) ob_get_clean();
    }
}
