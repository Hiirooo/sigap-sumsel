<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class RemoteFileCache
{
    public function get(string $sourcePath): ?string
    {
        $cachePath = $this->path($sourcePath);

        return Storage::disk('local')->exists($cachePath) ? $cachePath : null;
    }

    public function remember(string $sourceDisk, string $sourcePath): string
    {
        if ($cachePath = $this->get($sourcePath)) {
            return $cachePath;
        }

        $cachePath = $this->path($sourcePath);
        Storage::disk('local')->put($cachePath, Storage::disk($sourceDisk)->get($sourcePath));

        return $cachePath;
    }

    public function store(string $sourcePath, string $contents): void
    {
        Storage::disk('local')->put($this->path($sourcePath), $contents);
    }

    public function publish(string $sourcePath, string $contents): string
    {
        $publicPath = $this->publicPath($sourcePath);
        Storage::disk('public')->put($publicPath, $contents);

        return Storage::disk('public')->url($publicPath);
    }

    public function publicUrl(string $sourcePath): ?string
    {
        $publicPath = $this->publicPath($sourcePath);

        return Storage::disk('public')->exists($publicPath)
            ? Storage::disk('public')->url($publicPath)
            : null;
    }

    public function forget(?string $sourcePath): void
    {
        if ($sourcePath) {
            Storage::disk('local')->delete($this->path($sourcePath));
            Storage::disk('public')->delete($this->publicPath($sourcePath));
        }
    }

    private function path(string $sourcePath): string
    {
        $extension = strtolower(pathinfo(parse_url($sourcePath, PHP_URL_PATH) ?: $sourcePath, PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]{2,5}$/', $extension) ? '.'.$extension : '';

        return 'cache/remote/'.sha1($sourcePath).$extension;
    }

    private function publicPath(string $sourcePath): string
    {
        $extension = strtolower(pathinfo(parse_url($sourcePath, PHP_URL_PATH) ?: $sourcePath, PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]{2,5}$/', $extension) ? '.'.$extension : '';

        return 'cache/rilis/'.sha1($sourcePath).$extension;
    }
}
