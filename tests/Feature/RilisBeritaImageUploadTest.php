<?php

namespace Tests\Feature;

use App\Models\RilisBerita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_operator_can_preview_a_sumselprov_news_url(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $url = 'https://sumselprov.go.id/page/berita/berita-digital-sumsel';
        Http::fake([
            $url => Http::response($this->sumselprovArticleHtml(), 200, ['Content-Type' => 'text/html']),
        ]);

        $this->actingAs($operator)
            ->postJson(route('rilis-berita.preview-url'), ['url' => $url])
            ->assertOk()
            ->assertJson([
                'judul' => 'Berita Digital Sumsel',
                'isi' => '<p>Paragraf pertama berita.</p><p>Paragraf kedua berita.</p>',
                'tanggal_rilis' => '2026-07-27',
                'penulis' => 'Tim Liputan Diskominfo Sumsel',
                'media_publikasi' => 'sumselprov.go.id',
                'sumber_url' => $url,
                'image_urls' => [
                    'https://sumselprov.go.id/storage/berita/utama.jpg',
                    'https://sumselprov.go.id/storage/berita/pendukung.jpg',
                ],
            ]);
    }

    public function test_operator_can_store_a_release_with_images_imported_from_url(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Http::fake([
            'https://sumselprov.go.id/storage/berita/*' => Http::response($image, 200, ['Content-Type' => 'image/png']),
        ]);

        $this->actingAs($operator)->post(route('rilis-berita.store'), [
            'judul' => 'Berita dari Tautan',
            'isi' => '<p>Isi berita dari tautan.</p>',
            'tanggal_rilis' => '2026-07-27',
            'penulis' => 'Tim Liputan Diskominfo Sumsel',
            'media_publikasi' => 'sumselprov.go.id',
            'status' => 'draft',
            'sumber_url' => 'https://sumselprov.go.id/page/berita/berita-dari-tautan',
            'imported_image_urls' => [
                'https://sumselprov.go.id/storage/berita/utama.png',
                'https://sumselprov.go.id/storage/berita/pendukung.png',
            ],
        ])->assertRedirect(route('rilis-berita.index'));

        $rilis = RilisBerita::where('slug', 'berita-dari-tautan')->firstOrFail();

        $this->assertSame('https://sumselprov.go.id/page/berita/berita-dari-tautan', $rilis->sumber_url);
        $this->assertStringEndsWith('.webp', $rilis->gambar_utama);
        $this->assertCount(1, $rilis->gambar_pendukung);
        Storage::disk('local')->assertExists($rilis->gambar_utama);
        Storage::disk('local')->assertExists($rilis->gambar_pendukung[0]);
    }

    private function sumselprovArticleHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html><body><main>
    <section>
        <h1>Berita Digital Sumsel</h1>
        <span>Penulis: Tim Liputan Diskominfo Sumsel</span>
        <span>27 July 2026 16:04 WIB</span>
    </section>
    <div>
        <div>
            <img src="https://sumselprov.go.id/storage/berita/utama.jpg">
            <img src="/storage/berita/pendukung.jpg">
        </div>
        <article class="prose max-w-none">
            <p>Paragraf pertama berita.</p>
            <p>Paragraf kedua berita.</p>
        </article>
    </div>
</main></body></html>
HTML;
    }
}
