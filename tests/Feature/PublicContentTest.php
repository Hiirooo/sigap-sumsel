<?php

namespace Tests\Feature;

use App\Models\Dokumentasi;
use App\Models\Kliping;
use App\Models\RilisBerita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_indexes_only_show_approved_content(): void
    {
        RilisBerita::create([
            'judul' => 'Rilis Publik', 'slug' => 'rilis-publik', 'isi' => '<p>Isi publik</p>',
            'tanggal_rilis' => '2026-07-20', 'status' => 'terpublikasi',
        ]);
        RilisBerita::create([
            'judul' => 'Rilis Draft', 'slug' => 'rilis-draft', 'isi' => 'Rahasia',
            'tanggal_rilis' => '2026-07-21', 'status' => 'draft',
        ]);

        Kliping::create([
            'judul' => 'Kliping Publik', 'media' => 'Media Sumsel', 'tanggal' => '2026-07-20',
            'sentimen' => 'positif', 'status' => 'terpublikasi',
        ]);
        Kliping::create([
            'judul' => 'Kliping Arsip', 'media' => 'Media Sumsel', 'tanggal' => '2026-07-21',
            'sentimen' => 'netral', 'status' => 'terpublikasi', 'is_archived' => true,
        ]);

        Dokumentasi::create([
            'judul' => 'Galeri Publik', 'narasi' => 'Kegiatan publik', 'tanggal' => '2026-07-20',
            'jenis_media' => 'foto', 'file_path' => 'private/galeri-publik.jpg', 'status_verifikasi' => 'terverifikasi',
            'status_digitalisasi' => 'sudah_didigitalisasi',
        ]);
        Dokumentasi::create([
            'judul' => 'Galeri Draft', 'tanggal' => '2026-07-21', 'jenis_media' => 'foto',
            'file_path' => 'private/galeri-draft.jpg', 'status_verifikasi' => 'draft', 'status_digitalisasi' => 'belum_didigitalisasi',
        ]);

        $this->get(route('public.rilis.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ContentIndex')
                ->where('type', 'rilis')
                ->has('items.data', 1)
                ->where('items.data.0.title', 'Rilis Publik')
                ->missing('items.data.0.gambar_utama'));

        $this->get(route('public.kliping.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ContentIndex')
                ->where('type', 'kliping')
                ->has('items.data', 1)
                ->where('items.data.0.title', 'Kliping Publik')
                ->missing('items.data.0.file_path'));

        $this->get(route('public.galeri.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ContentIndex')
                ->where('type', 'galeri')
                ->has('items.data', 1)
                ->where('items.data.0.title', 'Galeri Publik')
                ->missing('items.data.0.file_path'));
    }

    public function test_guests_can_open_published_details_and_media_urls_are_safe(): void
    {
        $rilis = RilisBerita::create([
            'judul' => 'Rilis Detail', 'slug' => 'rilis-detail', 'isi' => '<p>Isi <strong>resmi</strong></p>',
            'tanggal_rilis' => '2026-07-20', 'status' => 'terpublikasi',
            'gambar_utama' => 'private/rilis.jpg',
        ]);
        $kliping = Kliping::create([
            'judul' => 'Kliping Detail', 'media' => 'Media Sumsel', 'tanggal' => '2026-07-20',
            'isi_berita' => 'Isi kliping', 'sentimen' => 'positif', 'status' => 'terpublikasi',
            'file_path' => 'private/kliping.pdf',
        ]);
        $dokumentasi = Dokumentasi::create([
            'judul' => 'Galeri Detail', 'narasi' => 'Narasi kegiatan', 'tanggal' => '2026-07-20',
            'jenis_media' => 'foto', 'file_path' => 'private/galeri-detail.jpg', 'status_verifikasi' => 'terverifikasi',
            'status_digitalisasi' => 'sudah_didigitalisasi',
        ]);
        $dokumentasi->mediaItems()->create([
            'jenis_media' => 'foto', 'file_path' => 'private/foto.jpg', 'urutan' => 0,
        ]);

        $this->get(route('public.rilis.show', $rilis->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ContentShow')
                ->where('item.content', 'Isi resmi')
                ->where('item.image_url', fn (string $url) => str_contains($url, 'signature='))
                ->missing('item.gambar_utama'));

        $this->get(route('public.kliping.show', $kliping))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ContentShow')
                ->where('item.file_url', fn (string $url) => str_contains($url, 'signature='))
                ->missing('item.file_path'));

        $this->get(route('public.galeri.show', $dokumentasi))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/ContentShow')
                ->has('item.media', 1)
                ->where('item.media.0.file_url', fn (string $url) => str_contains($url, 'signature='))
                ->missing('item.media.0.file_path'));
    }

    public function test_unapproved_details_return_not_found(): void
    {
        $rilis = RilisBerita::create([
            'judul' => 'Rilis Draft', 'slug' => 'draft', 'isi' => 'Draft',
            'tanggal_rilis' => '2026-07-20', 'status' => 'draft',
        ]);
        $kliping = Kliping::create([
            'judul' => 'Kliping Draft', 'media' => 'Media', 'tanggal' => '2026-07-20',
            'sentimen' => 'netral', 'status' => 'draft',
        ]);
        $dokumentasi = Dokumentasi::create([
            'judul' => 'Galeri Draft', 'tanggal' => '2026-07-20', 'jenis_media' => 'foto',
            'file_path' => 'private/galeri-draft.jpg', 'status_verifikasi' => 'draft', 'status_digitalisasi' => 'belum_didigitalisasi',
        ]);

        $this->get(route('public.rilis.show', $rilis->slug))->assertNotFound();
        $this->get(route('public.kliping.show', $kliping))->assertNotFound();
        $this->get(route('public.galeri.show', $dokumentasi))->assertNotFound();
    }
}
