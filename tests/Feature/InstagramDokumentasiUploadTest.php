<?php

namespace Tests\Feature;

use App\Models\Dokumentasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstagramDokumentasiUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_retrying_an_uploaded_media_hash_does_not_create_a_duplicate(): void
    {
        $this->configureUploadStorage();
        [$content, $hash] = $this->fakeImageContent();

        $this->uploadMedia($content, $hash)
            ->assertOk()
            ->assertJsonPath('media_count', 1)
            ->assertJsonPath('duplicate_count', 0);

        $this->uploadMedia($content, $hash)
            ->assertOk()
            ->assertJsonPath('media_count', 0)
            ->assertJsonPath('duplicate_count', 1);

        $dokumentasi = Dokumentasi::whereDate('tanggal', '2026-06-04')->firstOrFail();
        $this->assertCount(1, $dokumentasi->mediaItems);
        $this->assertSame($hash, $dokumentasi->mediaItems->first()->content_hash);
    }

    public function test_retry_detects_legacy_media_without_a_stored_hash(): void
    {
        $this->configureUploadStorage();
        [$content, $hash] = $this->fakeImageContent();

        $this->uploadMedia($content, $hash)->assertOk();
        $dokumentasi = Dokumentasi::whereDate('tanggal', '2026-06-04')->firstOrFail();
        $media = $dokumentasi->mediaItems->firstOrFail();
        $media->update(['content_hash' => null]);

        $this->uploadMedia($content, $hash)
            ->assertOk()
            ->assertJsonPath('media_count', 0)
            ->assertJsonPath('duplicate_count', 1);

        $this->assertCount(1, $dokumentasi->fresh()->mediaItems);
        $this->assertSame($hash, $media->fresh()->content_hash);
    }

    public function test_upload_keeps_multiple_activities_on_the_same_date_separate(): void
    {
        $this->configureUploadStorage();
        [$firstContent, $firstHash] = $this->fakeImageContent();
        [$secondContent, $secondHash] = $this->fakeImageContent();

        $this->uploadMedia($firstContent, $firstHash, 'Kegiatan Pertama', 'Narasi kegiatan pertama')->assertOk();
        $this->uploadMedia($secondContent, $secondHash, 'Kegiatan Kedua', 'Narasi kegiatan kedua')->assertOk();

        $this->assertDatabaseHas('dokumentasis', [
            'judul' => 'Kegiatan Pertama',
            'tanggal' => '2026-06-04',
            'narasi' => 'Narasi kegiatan pertama',
        ]);
        $this->assertDatabaseHas('dokumentasis', [
            'judul' => 'Kegiatan Kedua',
            'tanggal' => '2026-06-04',
            'narasi' => 'Narasi kegiatan kedua',
        ]);
    }

    private function configureUploadStorage(): void
    {
        config([
            'services.app_bhp.token' => 'test-token',
            'services.dokumentasi.storage_disk' => 'local',
        ]);
        Storage::fake('local');
    }

    private function fakeImageContent(): array
    {
        $image = UploadedFile::fake()->image('1.jpg');
        $content = $image->getContent();

        return [$content, hash('sha256', $content)];
    }

    private function uploadMedia(string $content, string $hash, ?string $judul = null, ?string $narasi = null)
    {
        return $this->withHeader('X-SIGAP-TOKEN', 'test-token')->post(
            '/api/v1/integrations/instagram/dokumentasi',
            array_filter([
                'username' => 'feed-by-date',
                'activity_date' => '2026-06-04',
                'judul' => $judul,
                'narasi' => $narasi,
                'media_hashes' => [$hash],
                'photos' => [UploadedFile::fake()->createWithContent('1.jpg', $content)],
            ], fn ($value) => $value !== null),
        );
    }
}
