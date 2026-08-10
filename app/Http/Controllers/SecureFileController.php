<?php

namespace App\Http\Controllers;

use App\Models\ArsipStatis;
use App\Models\Dokumentasi;
use App\Models\DokumentasiMedia;
use App\Models\Kliping;
use App\Models\RilisBerita;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Services\RemoteFileCache;
use App\Services\ArticleSentimentService;

class SecureFileController extends Controller
{
    private array $disks = ['local', 'google-drive', 'public'];

    public function __construct(
        private RemoteFileCache $fileCache,
        private ArticleSentimentService $articleService,
    )
    {
    }

    public function dokumentasi(Dokumentasi $dokumentasi): Response
    {
        $preferredDisk = (string) config('services.dokumentasi.storage_disk', 'local');

        return $this->response($dokumentasi->file_path, [$preferredDisk, ...$this->disks]);
    }

    public function dokumentasiThumbnail(Dokumentasi $dokumentasi): Response
    {
        $preferredDisk = (string) config('services.dokumentasi.storage_disk', 'local');

        return $this->response($dokumentasi->thumbnail_path, [$preferredDisk, ...$this->disks]);
    }

    public function dokumentasiMedia(DokumentasiMedia $media): Response
    {
        $preferredDisk = (string) config('services.dokumentasi.storage_disk', 'local');

        return $this->response($media->file_path, [$preferredDisk, ...$this->disks]);
    }

    public function dokumentasiMediaThumbnail(DokumentasiMedia $media): Response
    {
        $preferredDisk = (string) config('services.dokumentasi.storage_disk', 'local');

        return $this->response($media->thumbnail_path, [$preferredDisk, ...$this->disks]);
    }

    public function kliping(Kliping $kliping): Response
    {
        return $this->response(
            $kliping->file_path,
            fallbackUrl: $kliping->url,
            recover: fn () => $this->articleService->recoverKlipingImage($kliping),
        );
    }

    public function rilisImage(RilisBerita $rilis): Response
    {
        return $this->response($rilis->gambar_utama);
    }

    public function rilisSupportingImage(RilisBerita $rilis, int $index): Response
    {
        $images = array_values($rilis->gambar_pendukung ?? []);

        abort_unless(array_key_exists($index, $images), 404);

        return $this->response($images[$index]);
    }

    public function arsip(ArsipStatis $arsip): Response
    {
        return $this->response($arsip->file_path);
    }

    private function response(?string $path, ?array $disks = null, ?string $fallbackUrl = null, ?callable $recover = null): Response
    {
        if ($path) {
            $path = str_starts_with($path, '/storage/')
                ? ltrim(str_replace('/storage/', '', $path), '/')
                : (str_starts_with($path, 'public/') ? substr($path, 7) : $path);

            foreach (array_unique($disks ?? $this->disks) as $disk) {
                if ($disk === 'google-drive' && ($cachePath = $this->fileCache->get($path))) {
                    return $this->localResponse('local', $cachePath);
                }

                if (Storage::disk($disk)->exists($path)) {
                    if ($disk === 'google-drive') {
                        $cachePath = $this->fileCache->remember($disk, $path);
                        return $this->localResponse('local', $cachePath);
                    }

                    return $this->localResponse($disk, $path);
                }
            }
        }

        if ($recover && ($recoveredPath = $recover())) {
            return $this->response($recoveredPath, $disks, $fallbackUrl);
        }

        if ($this->isSafeExternalUrl($fallbackUrl)) {
            return redirect()->away($fallbackUrl);
        }

        abort(404);
    }

    private function isSafeExternalUrl(?string $url): bool
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function cacheHeaders(): array
    {
        return [
            'Cache-Control' => 'public, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    private function localResponse(string $disk, string $path): Response
    {
        return response()->file(Storage::disk($disk)->path($path), $this->cacheHeaders());
    }
}
