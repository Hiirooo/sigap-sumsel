<?php

namespace Tests\Feature;

use App\Models\Dokumentasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DokumentasiStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_documentation_upload_uses_the_configured_local_disk(): void
    {
        config(['services.dokumentasi.storage_disk' => 'local']);
        Storage::fake('local');
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post(route('dokumentasi.store'), [
            'judul' => 'Dokumentasi Lokal',
            'tanggal' => '2026-07-14',
            'status_verifikasi' => 'draft',
            'status_digitalisasi' => 'belum_didigitalisasi',
            'files' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect(route('dokumentasi.index'));

        $dokumentasi = Dokumentasi::where('judul', 'Dokumentasi Lokal')->firstOrFail();
        Storage::disk('local')->assertExists($dokumentasi->file_path);
    }

    public function test_migration_command_moves_documentation_from_drive_to_local(): void
    {
        config(['services.dokumentasi.storage_disk' => 'local']);
        Storage::fake('local');
        Storage::fake('google-drive');
        $path = 'uploads/dokumentasi/lama.jpg';
        Storage::disk('google-drive')->put($path, 'image-content');
        Dokumentasi::create([
            'judul' => 'Dokumentasi Lama',
            'tanggal' => '2026-07-14',
            'jenis_media' => 'foto',
            'file_path' => $path,
            'status_verifikasi' => 'draft',
            'status_digitalisasi' => 'belum_didigitalisasi',
        ]);

        $this->artisan('storage:migrate-dokumentasi')
            ->expectsOutput('Migrasi dokumentasi selesai: 1 dipindahkan, 0 dilewati.')
            ->assertSuccessful();

        Storage::disk('local')->assertExists($path);
        Storage::disk('google-drive')->assertMissing($path);
    }

    public function test_video_upload_stores_the_browser_generated_thumbnail(): void
    {
        config(['services.dokumentasi.storage_disk' => 'local']);
        Storage::fake('local');
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post(route('dokumentasi.store'), [
            'judul' => 'Video Dengan Thumbnail',
            'tanggal' => '2026-07-14',
            'status_verifikasi' => 'terverifikasi',
            'status_digitalisasi' => 'belum_didigitalisasi',
            'files' => [UploadedFile::fake()->create('video.mp4', 100, 'video/mp4')],
            'thumbnails' => [UploadedFile::fake()->image('thumbnail.webp')],
        ])->assertRedirect(route('dokumentasi.index'));

        $dokumentasi = Dokumentasi::where('judul', 'Video Dengan Thumbnail')->firstOrFail();
        Storage::disk('local')->assertExists($dokumentasi->file_path);
        Storage::disk('local')->assertExists($dokumentasi->thumbnail_path);
    }

    public function test_activity_can_store_multiple_mixed_media_files(): void
    {
        config(['services.dokumentasi.storage_disk' => 'local']);
        Storage::fake('local');
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post(route('dokumentasi.store'), [
            'judul' => 'Kegiatan Dengan Banyak Media',
            'tanggal' => '2026-07-15',
            'status_verifikasi' => 'terverifikasi',
            'status_digitalisasi' => 'belum_didigitalisasi',
            'files' => [
                UploadedFile::fake()->image('foto-1.jpg'),
                UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'),
                UploadedFile::fake()->image('foto-2.png'),
            ],
            'thumbnails' => [
                1 => UploadedFile::fake()->image('video.webp'),
            ],
        ])->assertRedirect(route('dokumentasi.index'));

        $dokumentasi = Dokumentasi::with('mediaItems')->where('judul', 'Kegiatan Dengan Banyak Media')->firstOrFail();
        $this->assertCount(3, $dokumentasi->mediaItems);
        $this->assertSame(['foto', 'video', 'foto'], $dokumentasi->mediaItems->pluck('jenis_media')->all());
        $this->assertNotNull($dokumentasi->mediaItems[1]->thumbnail_path);
    }
}
