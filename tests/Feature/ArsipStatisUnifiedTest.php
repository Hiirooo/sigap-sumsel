<?php

namespace Tests\Feature;

use App\Models\ArsipStatis;
use App\Models\Dokumentasi;
use App\Models\Kliping;
use App\Models\RilisBerita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArsipStatisUnifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_page_only_shows_employee_archive_records(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        ArsipStatis::create([
            'judul' => 'Arsip Manual',
            'tanggal_asli' => '2026-07-21',
            'jenis_asli' => 'fisik',
            'file_path' => 'uploads/arsip/manual.pdf',
        ]);
        RilisBerita::create([
            'judul' => 'Rilis Arsip',
            'slug' => 'rilis-arsip',
            'isi' => '<p>Isi rilis.</p>',
            'tanggal_rilis' => '2026-07-24',
            'status' => 'terpublikasi',
            'is_archived' => true,
        ]);
        RilisBerita::create([
            'judul' => 'Rilis Draft',
            'slug' => 'rilis-draft',
            'isi' => '<p>Isi draft.</p>',
            'tanggal_rilis' => '2026-07-25',
            'status' => 'draft',
        ]);
        Kliping::create([
            'judul' => 'Kliping Arsip',
            'media' => 'Media Sumsel',
            'tanggal' => '2026-07-23',
            'sentimen' => 'netral',
            'status' => 'draft',
            'is_archived' => true,
        ]);
        Dokumentasi::create([
            'judul' => 'Dokumentasi Arsip',
            'tanggal' => '2026-07-22',
            'jenis_media' => 'foto',
            'file_path' => 'uploads/dokumentasi/arsip.jpg',
            'status_verifikasi' => 'terverifikasi',
            'status_digitalisasi' => 'sudah_didigitalisasi',
            'is_archived' => true,
        ]);

        $this->actingAs($operator)
            ->get(route('arsip-statis.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ArsipStatis/Index')
                ->has('arsip', 1)
                ->where('arsip.0.judul', 'Arsip Manual'));

        $this->actingAs($operator)
            ->get(route('arsip-statis.index', ['jenis_asli' => 'fisik']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('arsip', 1)
                ->where('arsip.0.judul', 'Arsip Manual')
                ->where('filters.jenis_asli', 'fisik'));
    }
}
