<?php

namespace App\Services;

use App\Models\RilisBerita;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class SumselprovNewsImporter
{
    private const BASE_URL = 'https://sumselprov.go.id';

    public function __construct(private RemoteFileCache $fileCache)
    {
    }

    public function import(?int $maxPages = null, ?string $endpoint = null): array
    {
        $maxPages = max(1, min($maxPages ?? (int) config('services.sumselprov.max_pages', 5), 33));
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        for ($page = 1; $page <= $maxPages; $page++) {
            $pageResult = $this->importPage($page, $endpoint);

            foreach (['created', 'updated', 'skipped', 'failed'] as $key) {
                $result[$key] += $pageResult[$key];
            }

            if ($pageResult['item_count'] === 0 || $page >= $pageResult['last_page']) {
                break;
            }
        }

        return $result;
    }

    public function importPage(int $page, ?string $endpoint = null): array
    {
        $payload = $this->fetchPage($page, $endpoint);
        $items = $payload['data'] ?? [];
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'item_count' => count($items),
            'current_page' => (int) ($payload['current_page'] ?? $page),
            'last_page' => (int) ($payload['last_page'] ?? $page),
        ];

        foreach ($items as $item) {
            try {
                $this->importItem($item, $result);
            } catch (\Throwable $exception) {
                $result['failed']++;
                Log::warning('Berita Sumselprov gagal diimpor.', [
                    'slug' => $item['slug'] ?? null,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    public function previewPage(int $page, ?string $endpoint = null): array
    {
        $payload = $this->fetchPage($page, $endpoint);
        $items = collect($payload['data'] ?? [])->map(fn (array $item) => [
            'slug' => (string) ($item['slug'] ?? ''),
            'judul' => (string) ($item['judul'] ?? ''),
            'tgl' => $item['tgl'] ?? null,
            'filegambar' => $item['filegambar'] ?? null,
        ])->filter(fn (array $item) => $item['slug'] !== '')->values();
        $existingSlugs = RilisBerita::whereIn('slug', $items->pluck('slug'))->pluck('slug')->flip();

        return [
            'items' => $items->map(fn (array $item) => [
                ...$item,
                'duplicate' => $existingSlugs->has($item['slug']),
            ])->all(),
            'current_page' => (int) ($payload['current_page'] ?? $page),
            'last_page' => (int) ($payload['last_page'] ?? $page),
        ];
    }

    public function resolveItem(array $item, string $action): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $slug = (string) ($item['slug'] ?? '');
        $existing = RilisBerita::where('slug', $slug)->first();

        if (! $existing) {
            $values = $this->buildImportedValues($item, $slug);
            RilisBerita::create(['slug' => $slug, ...$values]);
            $result['created']++;
            return $result;
        }

        if ($action === 'skip' || $action === 'import') {
            $result['skipped']++;
            return $result;
        }

        $values = $this->buildImportedValues($item, $slug.'-'.Str::uuid());

        try {
            if ($action === 'delete_reimport') {
                $this->deleteReleaseImages($existing);
                $existing->delete();
                RilisBerita::create(['slug' => $slug, ...$values]);
                $result['created']++;
            } elseif ($action === 'overwrite') {
                $oldPrimary = $existing->gambar_utama;
                $existing->update($values);
                if ($oldPrimary && $oldPrimary !== $values['gambar_utama']) {
                    $this->deleteStoredImage($oldPrimary);
                }
                $result['updated']++;
            } else {
                throw new \InvalidArgumentException('Tindakan konflik tidak valid.');
            }
        } catch (\Throwable $exception) {
            if ($values['gambar_utama'] ?? null) {
                $this->deleteStoredImage($values['gambar_utama']);
            }
            throw $exception;
        }

        return $result;
    }

    public function previewUrl(string $url): array
    {
        $this->ensureAllowedSumselprovUrl($url, '/page/berita/');

        $response = Http::accept('text/html')
            ->withOptions(['allow_redirects' => false])
            ->timeout(20)
            ->retry(1, 500)
            ->get($url)
            ->throw();

        if (strlen($response->body()) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Halaman berita melebihi batas 5 MB.');
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \RuntimeException('Halaman berita tidak dapat dibaca.');
        }

        $xpath = new DOMXPath($document);
        $titleNode = $xpath->query('//main//h1')->item(0);
        $articleNode = $xpath->query('//main//article[contains(concat(" ", normalize-space(@class), " "), " prose ")]')->item(0);

        if (! $titleNode || ! $articleNode) {
            throw new \RuntimeException('Judul atau isi berita tidak ditemukan pada halaman.');
        }

        $title = trim(preg_replace('/\s+/', ' ', $titleNode->textContent));
        $paragraphs = [];

        foreach ($xpath->query('.//p', $articleNode) as $paragraph) {
            $text = trim(preg_replace('/\s+/u', ' ', $paragraph->textContent));

            if ($text !== '') {
                $paragraphs[] = '<p>'.htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
            }
        }

        if ($paragraphs === []) {
            throw new \RuntimeException('Isi berita tidak ditemukan pada halaman.');
        }

        $author = 'Tim Liputan Diskominfo Sumsel';
        $authorNode = $xpath->query('//main//span[contains(normalize-space(.), "Penulis:")]')->item(0);

        if ($authorNode) {
            $author = trim(preg_replace('/^Penulis:\s*/i', '', trim($authorNode->textContent)));
        }

        $date = null;

        foreach ($xpath->query('//main//span[contains(normalize-space(.), "WIB")]') as $dateNode) {
            try {
                $date = Carbon::parse(trim(str_ireplace('WIB', '', $dateNode->textContent)))->toDateString();
                break;
            } catch (\Throwable) {
                continue;
            }
        }

        $images = [];

        foreach ($xpath->query('//main//article[contains(concat(" ", normalize-space(@class), " "), " prose ")]/preceding::img[contains(@src, "/storage/berita/")]') as $imageNode) {
            if (! $imageNode instanceof DOMElement) {
                continue;
            }

            $imageUrl = $this->absoluteSumselprovUrl($imageNode->getAttribute('src'));

            if ($imageUrl && ! in_array($imageUrl, $images, true)) {
                $images[] = $imageUrl;
            }
        }

        if ($images === []) {
            throw new \RuntimeException('Gambar berita tidak ditemukan pada halaman.');
        }

        return [
            'judul' => $title,
            'isi' => implode('', $paragraphs),
            'tanggal_rilis' => $date ?? now()->toDateString(),
            'penulis' => $author,
            'media_publikasi' => 'sumselprov.go.id',
            'sumber_url' => $url,
            'image_urls' => array_slice($images, 0, 11),
        ];
    }

    public function storeRemoteImage(string $url, string $name): string
    {
        $this->ensureAllowedSumselprovUrl($url, '/storage/berita/');

        return $this->downloadImage($url, $name);
    }

    private function importItem(array $item, array &$result): void
    {
        $slug = (string) ($item['slug'] ?? '');

        if ($slug === '') {
            $result['failed']++;
            return;
        }

        $existing = RilisBerita::where('slug', $slug)->first();

        if ($existing && ! str_starts_with((string) $existing->sumber_url, self::BASE_URL)) {
            $result['skipped']++;
            return;
        }

        if ($existing) {
            if (! $existing->gambar_utama || str_starts_with($existing->gambar_utama, 'http')) {
                $imageUrl = $this->firstImage($existing->gambar_utama ?: ($item['filegambar'] ?? null));

                if ($imageUrl) {
                    $existing->update(['gambar_utama' => $this->downloadImage($imageUrl, $slug)]);
                    $result['updated']++;
                    return;
                }
            }

            if ($this->shouldConvertWebp() && ! str_ends_with(strtolower((string) $existing->gambar_utama), '.webp')) {
                $webpPath = $this->convertStoredImageToWebp($existing->gambar_utama, $slug);

                if (! $webpPath) {
                    $imageUrl = $this->firstImage($item['filegambar'] ?? null);
                    $webpPath = $imageUrl ? $this->downloadImage($imageUrl, $slug) : null;
                }

                if ($webpPath) {
                    $oldPath = $existing->gambar_utama;
                    $existing->update(['gambar_utama' => $webpPath]);
                    $this->deleteStoredImage($oldPath);
                    $result['updated']++;
                    return;
                }
            }

            if ($this->moveToConfiguredDisk($existing->gambar_utama)) {
                $result['updated']++;
                return;
            }

            $result['skipped']++;
            return;
        }

        RilisBerita::create(['slug' => $slug, ...$this->buildImportedValues($item, $slug)]);
        $result['created']++;
    }

    private function fetchPage(int $page, ?string $endpoint = null): array
    {
        $endpoints = $endpoint !== null
            ? [$endpoint]
            : (config('services.sumselprov.api_endpoints', ['api_berita_all2']) ?: ['api_berita_all2']);

        $combinedItems = [];
        $seenSlugs = [];
        $emptyPayload = null;
        $lastException = null;

        foreach ($endpoints as $ep) {
            $ep = trim((string) $ep);

            if ($ep === '') {
                continue;
            }

            try {
                $httpResponse = Http::acceptJson()
                    ->timeout(20)
                    ->retry(1, 500)
                    ->get(self::BASE_URL.'/api/sumselprov/'.$ep, ['page' => max(1, $page)]);

                if (! $httpResponse->successful()) {
                    $lastException = new \RuntimeException('Endpoint Sumselprov '.$ep.' mengembalikan HTTP '.$httpResponse->status().'.');
                    continue;
                }

                $payload = $this->normalizePagePayload($httpResponse->json(), $page);

                if ($payload === null) {
                    continue;
                }

                $items = $payload['data'] ?? [];

                if (count($items) === 0) {
                    $emptyPayload ??= $payload;
                    continue;
                }

                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $slug = (string) ($item['slug'] ?? '');
                    $key = $slug !== '' ? $slug : md5(json_encode($item));

                    if (isset($seenSlugs[$key])) {
                        continue;
                    }

                    $seenSlugs[$key] = true;
                    $combinedItems[] = $item;
                }

                $emptyPayload = [
                    'data' => [],
                    'current_page' => $page,
                    'last_page' => max((int) ($emptyPayload['last_page'] ?? $page), (int) ($payload['last_page'] ?? $page)),
                ];

            } catch (\Throwable $e) {
                $lastException = $e;
                continue;
            }
        }

        if ($combinedItems !== []) {
            return [
                'data' => $combinedItems,
                'current_page' => $page,
                'last_page' => (int) ($emptyPayload['last_page'] ?? $page),
            ];
        }

        if ($emptyPayload !== null) {
            return $emptyPayload;
        }

        throw $lastException ?? new \RuntimeException('Semua endpoint Sumselprov tidak dapat diakses.');
    }

    private function normalizePagePayload(mixed $response, int $page): ?array
    {
        if (! is_array($response)) {
            return null;
        }

        if (array_is_list($response)) {
            return [
                'data' => $response,
                'current_page' => $page,
                'last_page' => $page,
            ];
        }

        if (! array_key_exists('data', $response) || ! is_array($response['data'])) {
            return null;
        }

        return [
            'data' => $response['data'],
            'current_page' => (int) ($response['current_page'] ?? $page),
            'last_page' => (int) ($response['last_page'] ?? $page),
        ];
    }

    private function buildImportedValues(array $item, string $imageName): array
    {
        $slug = (string) $item['slug'];
        $detailUrl = self::BASE_URL.'/api/sumselprov/beritadetailslug?judul='.urlencode($slug);
        $detailResponse = Http::acceptJson()
            ->timeout(20)
            ->retry(1, 500)
            ->get($detailUrl);
        $detail = $detailResponse->successful() ? ($detailResponse->json() ?? []) : [];
        $imageUrl = $this->firstImage($detail['gambar'] ?? $item['filegambar'] ?? null);

        return [
            'judul' => $item['judul'] ?? $detail['judul'] ?? $slug,
            'isi' => $detail['isi'] ?? '',
            'tanggal_rilis' => Carbon::parse($item['tgl'] ?? $detail['tanggal'] ?? now())->toDateString(),
            'penulis' => 'Pemerintah Provinsi Sumatera Selatan',
            'media_publikasi' => 'sumselprov.go.id',
            'gambar_utama' => $imageUrl ? $this->downloadImage($imageUrl, $imageName) : null,
            'sumber_url' => $detailUrl,
            'status' => 'terpublikasi',
        ];
    }

    private function deleteReleaseImages(RilisBerita $rilis): void
    {
        $this->deleteStoredImage($rilis->gambar_utama);

        foreach ($rilis->gambar_pendukung ?? [] as $path) {
            $this->deleteStoredImage($path);
        }
    }

    private function firstImage(?string $images): ?string
    {
        $image = trim(explode(',', (string) $images)[0] ?? '');

        if ($image === '') {
            return null;
        }

        return str_starts_with($image, 'http')
            ? $image
            : self::BASE_URL.'/storage/'.ltrim(str_replace('public/', '', $image), '/');
    }

    private function ensureAllowedSumselprovUrl(string $url, string $requiredPathPrefix): void
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (($parts['scheme'] ?? null) !== 'https'
            || ! in_array($host, ['sumselprov.go.id', 'www.sumselprov.go.id'], true)
            || ! str_starts_with($path, $requiredPathPrefix)) {
            throw new \InvalidArgumentException('URL harus berasal dari halaman berita resmi sumselprov.go.id.');
        }
    }

    private function absoluteSumselprovUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        return str_starts_with($url, 'https://')
            ? $url
            : self::BASE_URL.'/'.ltrim($url, '/');
    }

    private function downloadImage(string $url, string $slug): string
    {
        $response = Http::timeout(30)->retry(3, 500)->get($url)->throw();
        $contents = $response->body();
        $mimeType = strtolower(trim(explode(';', $response->header('Content-Type', ''))[0]));
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (! in_array($mimeType, $allowedMimeTypes, true) || $contents === '' || strlen($contents) > 20 * 1024 * 1024) {
            throw new \RuntimeException('Gambar Sumselprov tidak valid atau melebihi batas 20 MB.');
        }

        return $this->shouldConvertWebp()
            ? $this->convertAndUploadWebp($contents, $slug)
            : $this->uploadOriginalImage($contents, $slug, $mimeType);
    }

    private function convertStoredImageToWebp(?string $path, string $slug): ?string
    {
        if (! $path) {
            return null;
        }

        foreach (array_unique([(string) config('filesystems.default'), 'google-drive', 'local', 'public']) as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $this->convertAndUploadWebp(Storage::disk($disk)->get($path), $slug);
            }
        }

        return null;
    }

    private function convertAndUploadWebp(string $contents, string $slug): string
    {
        if ($contents === '' || strlen($contents) > 20 * 1024 * 1024) {
            throw new \RuntimeException('Gambar Sumselprov tidak valid atau melebihi batas 20 MB.');
        }

        $tempDirectory = 'temp/sumselprov/'.Str::uuid();
        $sourcePath = $tempDirectory.'/source-image';
        $webpTempPath = $tempDirectory.'/converted.webp';
        $targetPath = 'uploads/rilis/sumselprov/'.$slug.'.webp';
        $localDisk = Storage::disk('local');
        $image = null;
        $resized = null;

        try {
            $localDisk->put($sourcePath, $contents);
            $image = @imagecreatefromstring($localDisk->get($sourcePath));

            if (! $image) {
                throw new \RuntimeException('Gambar Sumselprov tidak dapat diproses oleh GD.');
            }

            $image = $this->applyExifOrientation($image, $localDisk->path($sourcePath));
            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $maxWidth = max(320, (int) config('services.rilis.image_max_width', 1600));
            $targetWidth = min($sourceWidth, $maxWidth);
            $targetHeight = (int) round($sourceHeight * ($targetWidth / $sourceWidth));

            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

            $quality = max(60, min((int) config('services.rilis.image_webp_quality', 82), 95));

            if (! imagewebp($resized, $localDisk->path($webpTempPath), $quality)) {
                throw new \RuntimeException('Konversi gambar Sumselprov ke WebP gagal.');
            }

            $targetDisk = Storage::disk($this->imageDisk());

            if (! $targetDisk->exists($targetPath)) {
                $targetDisk->put($targetPath, $localDisk->get($webpTempPath), ['mimetype' => 'image/webp']);
            }

            $webpContents = $localDisk->get($webpTempPath);
            $this->fileCache->publish($targetPath, $webpContents);
            if ($this->imageDisk() === 'google-drive') {
                $this->fileCache->store($targetPath, $webpContents);
            }

            return $targetPath;
        } finally {
            if ($resized instanceof \GdImage) {
                imagedestroy($resized);
            }
            if ($image instanceof \GdImage) {
                imagedestroy($image);
            }
            $localDisk->deleteDirectory($tempDirectory);
        }
    }

    private function applyExifOrientation(\GdImage $image, string $sourcePath): \GdImage
    {
        $orientation = function_exists('exif_read_data')
            ? (@exif_read_data($sourcePath)['Orientation'] ?? 1)
            : 1;

        $angle = match ((int) $orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if (! $rotated) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    private function uploadOriginalImage(string $contents, string $slug, string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $extension = $extensions[$mimeType] ?? throw new \RuntimeException('Format gambar Sumselprov tidak didukung.');
        $tempDirectory = 'temp/sumselprov/'.Str::uuid();
        $tempPath = $tempDirectory.'/source.'.$extension;
        $targetPath = 'uploads/rilis/sumselprov/'.$slug.'.'.$extension;
        $localDisk = Storage::disk('local');

        try {
            $localDisk->put($tempPath, $contents);
            $targetDisk = Storage::disk($this->imageDisk());

            if (! $targetDisk->exists($targetPath)) {
                $targetDisk->put($targetPath, $localDisk->get($tempPath), ['mimetype' => $mimeType]);
            }

            $originalContents = $localDisk->get($tempPath);
            $this->fileCache->publish($targetPath, $originalContents);
            if ($this->imageDisk() === 'google-drive') {
                $this->fileCache->store($targetPath, $originalContents);
            }

            return $targetPath;
        } finally {
            $localDisk->deleteDirectory($tempDirectory);
        }
    }

    public function storeUploadedImage(UploadedFile $file, string $name): string
    {
        $mimeType = strtolower((string) $file->getMimeType());
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new \RuntimeException('Format gambar rilis tidak didukung.');
        }

        $contents = file_get_contents($file->getRealPath());

        return $this->shouldConvertWebp()
            ? $this->convertAndUploadWebp($contents, $name)
            : $this->uploadOriginalImage($contents, $name, $mimeType);
    }

    private function shouldConvertWebp(): bool
    {
        return (bool) config('services.rilis.image_convert_webp', true);
    }

    private function imageDisk(): string
    {
        $disk = (string) config('services.rilis.image_storage_disk', config('filesystems.default', 'local'));

        if (! in_array($disk, ['local', 'google-drive'], true)) {
            throw new \RuntimeException('RILIS_IMAGE_STORAGE_DISK harus local atau google-drive.');
        }

        return $disk;
    }

    public function deleteStoredImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        $this->fileCache->forget($path);

        foreach (array_unique([$this->imageDisk(), 'google-drive', 'local', 'public']) as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function moveToConfiguredDisk(?string $path): bool
    {
        $targetDisk = $this->imageDisk();

        if (! $path) {
            return false;
        }

        if (Storage::disk($targetDisk)->exists($path)) {
            return false;
        }

        foreach (array_diff(['google-drive', 'local', 'public'], [$targetDisk]) as $sourceDisk) {
            if (Storage::disk($sourceDisk)->exists($path)) {
                Storage::disk($targetDisk)->put($path, Storage::disk($sourceDisk)->get($path));
                Storage::disk($sourceDisk)->delete($path);
                return true;
            }
        }

        return false;
    }
}
