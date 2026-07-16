<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\RilisBerita;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        if (! $adminPassword) {
            throw new \RuntimeException('SEED_ADMIN_PASSWORD must be set before seeding.');
        }

        User::factory()->create([
            'name' => env('SEED_ADMIN_NAME', 'Local Admin'),
            'email' => env('SEED_ADMIN_EMAIL', 'admin@example.com'),
            'password' => Hash::make($adminPassword),
            'role' => 'admin',
        ]);

        RilisBerita::create([
            'judul' => 'Gubernur Sumsel Resmikan Program SIGAP',
            'slug' => 'gubernur-sumsel-resmikan-program-sigap',
            'isi' => 'Lorem ipsum dolor sit amet...',
            'tanggal_rilis' => '2026-07-01',
            'penulis' => 'Humas',
            'media_publikasi' => 'Website Resmi',
            'status' => 'terpublikasi',
        ]);

        RilisBerita::create([
            'judul' => 'Rapat Paripurna DPRD Provinsi Sumatera Selatan',
            'slug' => 'rapat-paripurna-dprd-provinsi-sumatera-selatan',
            'isi' => 'Lorem ipsum dolor sit amet...',
            'tanggal_rilis' => '2026-07-05',
            'penulis' => 'Humas',
            'media_publikasi' => 'Instagram',
            'status' => 'draft',
        ]);
        \App\Models\Dokumentasi::create([
            'judul' => 'Foto Bersama Peresmian SIGAP',
            'tanggal' => '2026-07-01',
            'jenis_media' => 'foto',
            'file_path' => '/storage/foto/sigap1.jpg',
            'pimpinan_terkait' => 'Gubernur',
            'status_verifikasi' => 'terverifikasi',
            'status_digitalisasi' => 'sudah_diarsipkan',
        ]);

        \App\Models\Dokumentasi::create([
            'judul' => 'Video Liputan Peresmian SIGAP',
            'tanggal' => '2026-07-01',
            'jenis_media' => 'video',
            'file_path' => '/storage/video/sigap1.mp4',
            'pimpinan_terkait' => 'Gubernur',
            'status_verifikasi' => 'draft',
            'status_digitalisasi' => 'belum_didigitalisasi',
        ]);
    }
}
