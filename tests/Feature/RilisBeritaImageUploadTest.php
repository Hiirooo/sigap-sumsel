<?php

namespace Tests\Feature;

use App\Models\RilisBerita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RilisBeritaImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.rilis.image_convert_webp' => true,
            'services.rilis.image_storage_disk' => 'local',
        ]);
        Storage::fake('local');
    }

    public function test_operator_can_upload_a_primary_and_multiple_supporting_images(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post(route('rilis-berita.store'), [
            'judul' => 'Rilis Dengan Galeri',
            'isi' => '<p>Isi berita.</p>',
            'tanggal_rilis' => '2026-07-14',
            'penulis' => 'Humas',
            'media_publikasi' => 'Website',
            'status' => 'terpublikasi',
            'gambar_utama' => UploadedFile::fake()->image('utama.jpg', 2000, 1000),
            'gambar_pendukung' => [
                UploadedFile::fake()->image('pendukung-1.png', 1200, 800),
                UploadedFile::fake()->image('pendukung-2.jpg', 1000, 700),
            ],
        ])->assertRedirect(route('rilis-berita.index'));

        $rilis = RilisBerita::where('slug', 'rilis-dengan-galeri')->firstOrFail();

        $this->assertStringEndsWith('.webp', $rilis->gambar_utama);
        $this->assertCount(2, $rilis->gambar_pendukung);
        Storage::disk('local')->assertExists($rilis->gambar_utama);

        foreach ($rilis->gambar_pendukung as $path) {
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('local')->assertExists($path);
        }

        $this->assertSame([], Storage::disk('local')->allFiles('temp/sumselprov'));
    }

    public function test_primary_image_is_required_for_a_manual_release(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)->post(route('rilis-berita.store'), [
            'judul' => 'Rilis Tanpa Gambar',
            'isi' => '<p>Isi berita.</p>',
            'tanggal_rilis' => '2026-07-14',
            'penulis' => 'Humas',
            'status' => 'draft',
        ])->assertSessionHasErrors('gambar_utama');

        $this->assertDatabaseMissing('rilis_beritas', ['slug' => 'rilis-tanpa-gambar']);
    }
}
