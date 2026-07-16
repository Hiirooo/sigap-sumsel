<?php

namespace Tests\Feature;

use App\Services\RemoteFileCache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RemoteFileCacheTest extends TestCase
{
    public function test_remote_file_is_cached_locally_and_can_be_forgotten(): void
    {
        Storage::fake('local');
        Storage::fake('google-drive');
        Storage::fake('public');
        $sourcePath = 'uploads/rilis/berita.webp';
        Storage::disk('google-drive')->put($sourcePath, 'webp-content');
        $cache = app(RemoteFileCache::class);

        $cachePath = $cache->remember('google-drive', $sourcePath);

        Storage::disk('local')->assertExists($cachePath);
        $this->assertSame($cachePath, $cache->get($sourcePath));
        $publicUrl = $cache->publish($sourcePath, 'webp-content');
        $this->assertStringContainsString('/storage/cache/rilis/', $publicUrl);
        $this->assertNotNull($cache->publicUrl($sourcePath));

        $cache->forget($sourcePath);
        Storage::disk('local')->assertMissing($cachePath);
        $this->assertNull($cache->publicUrl($sourcePath));
    }
}
