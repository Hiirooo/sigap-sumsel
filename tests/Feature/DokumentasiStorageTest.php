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
            'narasi' => 'Narasi dokumentasi lokal.',
            'tanggal' => '2026-07-14',
            'status_verifikasi' => 'draft',
            'status_digitalisasi' => 'belum_didigitalisasi',
            'files' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect(route('dokumentasi.index'));

        $dokumentasi = Dokumentasi::where('judul', 'Dokumentasi Lokal')->firstOrFail();
        $this->assertSame('Narasi dokumentasi lokal.', $dokumentasi->narasi);
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

    public function test_admin_can_delete_all_documentation_records_and_files(): void
    {
        config(['services.dokumentasi.storage_disk' => 'local']);
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 2) as $index) {
            $filePath = "uploads/dokumentasi/foto-{$index}.jpg";
            $thumbnailPath = "uploads/dokumentasi/thumbnails/foto-{$index}.jpg";
            Storage::disk('local')->put($filePath, 'image-content');
            Storage::disk('local')->put($thumbnailPath, 'thumbnail-content');

            $dokumentasi = Dokumentasi::create([
                'judul' => "Dokumentasi {$index}",
                'tanggal' => '2026-07-15',
                'jenis_media' => 'foto',
                'file_path' => $filePath,
                'thumbnail_path' => $thumbnailPath,
                'status_verifikasi' => 'draft',
                'status_digitalisasi' => 'belum_didigitalisasi',
            ]);
            $dokumentasi->mediaItems()->create([
                'jenis_media' => 'foto',
                'file_path' => $filePath,
                'thumbnail_path' => $thumbnailPath,
                'urutan' => 0,
            ]);
        }

        $this->actingAs($admin)
            ->delete(route('dokumentasi.destroy-all'))
            ->assertRedirect(route('dokumentasi.index'));

        $this->assertDatabaseCount('dokumentasis', 0);
        $this->assertDatabaseCount('dokumentasi_media', 0);
        Storage::disk('local')->assertMissing('uploads/dokumentasi/foto-1.jpg');
        Storage::disk('local')->assertMissing('uploads/dokumentasi/thumbnails/foto-1.jpg');
        Storage::disk('local')->assertMissing('uploads/dokumentasi/foto-2.jpg');
        Storage::disk('local')->assertMissing('uploads/dokumentasi/thumbnails/foto-2.jpg');
    }

    public function test_operator_cannot_delete_all_documentation(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        Dokumentasi::create([
            'judul' => 'Dokumentasi Tetap Ada',
            'tanggal' => '2026-07-15',
            'jenis_media' => 'foto',
            'file_path' => 'uploads/dokumentasi/foto.jpg',
            'status_verifikasi' => 'draft',
            'status_digitalisasi' => 'belum_didigitalisasi',
        ]);

        $this->actingAs($operator)
            ->delete(route('dokumentasi.destroy-all'))
            ->assertForbidden();

        $this->assertDatabaseCount('dokumentasis', 1);
    }

    public function test_operator_can_delete_only_selected_documentation_and_files(): void
    {
        config(['services.dokumentasi.storage_disk' => 'local']);
        Storage::fake('local');
        $operator = User::factory()->create(['role' => 'operator']);
        $documents = collect();

        foreach (range(1, 2) as $index) {
            $filePath = "uploads/dokumentasi/selected-{$index}.jpg";
            Storage::disk('local')->put($filePath, 'image-content');
            $document = Dokumentasi::create([
                'judul' => "Pilihan {$index}",
                'tanggal' => '2026-07-15',
                'jenis_media' => 'foto',
                'file_path' => $filePath,
                'status_verifikasi' => 'draft',
                'status_digitalisasi' => 'belum_didigitalisasi',
            ]);
            $document->mediaItems()->create([
                'jenis_media' => 'foto',
                'file_path' => $filePath,
                'urutan' => 0,
            ]);
            $documents->push($document);
        }

        $this->actingAs($operator)
            ->delete(route('dokumentasi.destroy-selected'), ['ids' => [$documents[0]->id]])
            ->assertRedirect(route('dokumentasi.index'));

        $this->assertDatabaseMissing('dokumentasis', ['id' => $documents[0]->id]);
        $this->assertDatabaseHas('dokumentasis', ['id' => $documents[1]->id]);
        $this->assertDatabaseMissing('dokumentasi_media', ['dokumentasi_id' => $documents[0]->id]);
        $this->assertDatabaseHas('dokumentasi_media', ['dokumentasi_id' => $documents[1]->id]);
        Storage::disk('local')->assertMissing('uploads/dokumentasi/selected-1.jpg');
        Storage::disk('local')->assertExists('uploads/dokumentasi/selected-2.jpg');
    }

    public function test_selected_deletion_requires_at_least_one_valid_document_id(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->delete(route('dokumentasi.destroy-selected'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }
}
