<?php

namespace Tests\Feature\Api;

use App\Models\Dokumentasi;
use App\Models\Kliping;
use App\Models\RilisBerita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppBhpContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.app_bhp.token' => 'test-token']);
    }

    public function test_api_rejects_requests_without_the_shared_token(): void
    {
        $this->getJson('/api/v1/app-bhp/rilis')->assertUnauthorized();
    }

    public function test_release_api_only_returns_published_content(): void
    {
        RilisBerita::create([
            'judul' => 'Rilis Publik', 'slug' => 'rilis-publik', 'isi' => 'Isi publik',
            'tanggal_rilis' => '2026-07-14', 'penulis' => 'Humas', 'status' => 'terpublikasi',
            'gambar_utama' => 'uploads/rilis/utama.webp',
            'gambar_pendukung' => ['uploads/rilis/pendukung-1.webp', 'uploads/rilis/pendukung-2.webp'],
        ]);
        RilisBerita::create([
            'judul' => 'Rilis Draft', 'slug' => 'rilis-draft', 'isi' => 'Isi draft',
            'tanggal_rilis' => '2026-07-14', 'penulis' => 'Humas', 'status' => 'draft',
        ]);

        $this->withHeader('X-SIGAP-TOKEN', 'test-token')
            ->getJson('/api/v1/app-bhp/rilis')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'rilis-publik')
            ->assertJsonCount(2, 'data.0.gambar_pendukung_urls')
            ->assertJsonMissingPath('data.0.gambar_utama')
            ->assertJsonMissingPath('data.0.gambar_pendukung');
    }

    public function test_kliping_and_documentation_api_only_return_approved_content(): void
    {
        Kliping::create([
            'judul' => 'Kliping Publik', 'media' => 'Media Sumsel', 'tanggal' => '2026-07-14',
            'sentimen' => 'positif', 'status' => 'terpublikasi',
        ]);
        Kliping::create([
            'judul' => 'Kliping Draft', 'media' => 'Media Sumsel', 'tanggal' => '2026-07-14',
            'sentimen' => 'netral', 'status' => 'draft',
        ]);
        $dokumentasi = Dokumentasi::create([
            'judul' => 'Dokumentasi Publik', 'tanggal' => '2026-07-14', 'jenis_media' => 'foto',
            'file_path' => 'uploads/dokumentasi/publik.jpg', 'status_verifikasi' => 'terverifikasi',
            'thumbnail_path' => 'uploads/dokumentasi/thumbnails/publik.webp',
            'status_digitalisasi' => 'sudah_didigitalisasi',
        ]);
        $dokumentasi->mediaItems()->createMany([
            ['jenis_media' => 'foto', 'file_path' => 'uploads/dokumentasi/publik.jpg', 'urutan' => 0],
            ['jenis_media' => 'video', 'file_path' => 'uploads/dokumentasi/publik.mp4', 'thumbnail_path' => 'uploads/dokumentasi/thumbnails/publik.webp', 'urutan' => 1],
        ]);
        Dokumentasi::create([
            'judul' => 'Dokumentasi Draft', 'tanggal' => '2026-07-14', 'jenis_media' => 'foto',
            'file_path' => 'uploads/dokumentasi/draft.jpg', 'status_verifikasi' => 'draft',
            'status_digitalisasi' => 'belum_didigitalisasi',
        ]);

        $headers = ['X-SIGAP-TOKEN' => 'test-token'];

        $this->withHeaders($headers)->getJson('/api/v1/app-bhp/kliping')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Kliping Publik');

        $this->withHeaders($headers)->getJson('/api/v1/app-bhp/dokumentasi')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'Dokumentasi Publik')
            ->assertJsonPath('data.0.jenis_media', 'campuran')
            ->assertJsonPath('data.0.media_count', 2)
            ->assertJsonCount(2, 'data.0.media')
            ->assertJsonPath('data.0.media.1.jenis_media', 'video')
            ->assertJsonMissingPath('data.0.file_path')
            ->assertJsonMissingPath('data.0.thumbnail_path')
            ->assertJsonPath('data.0.media.1.thumbnail_url', fn (string $url) => str_contains($url, 'signature='))
            ->assertJsonPath('data.0.file_url', fn (string $url) => str_contains($url, 'signature='));
    }
}
