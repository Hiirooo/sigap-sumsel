<?php

namespace Database\Seeders;

use App\Models\MonevChecklist;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonevInitialSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('role', 'admin')->value('id');
        $items = [
            [
                'aspek' => 'SOP Pengelolaan Dokumentasi',
                'indikator' => 'Dokumentasi kegiatan terinput maksimal H+1',
                'target' => 'Minimal 95% dokumentasi',
                'realisasi' => '96% dokumentasi',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'sedang',
                'catatan' => 'Pengiriman dokumentasi berjalan tertib dan mayoritas telah masuk maksimal H+1.',
                'rekomendasi' => 'Pertahankan koordinasi pengiriman bahan dan penetapan PIC setiap kegiatan.',
                'penanggung_jawab' => 'Operator Dokumentasi',
                'tenggat_tindak_lanjut' => '2026-08-05',
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Input Data dan Metadata',
                'indikator' => 'Kelengkapan judul, tanggal, pimpinan, dan narasi kegiatan',
                'target' => '100% metadata lengkap',
                'realisasi' => '100% metadata lengkap',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'rendah',
                'catatan' => 'Judul, tanggal, pimpinan, dan narasi telah dicatat secara lengkap.',
                'rekomendasi' => 'Pertahankan standar pemeriksaan metadata sebelum publikasi.',
                'penanggung_jawab' => 'Operator Dokumentasi',
                'tenggat_tindak_lanjut' => '2026-08-15',
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Input Data dan Metadata',
                'indikator' => 'Pencegahan media dokumentasi terduplikasi',
                'target' => 'Maksimal 1% media duplikat',
                'realisasi' => 'Kurang dari 1% media duplikat',
                'skor' => 98,
                'status' => 'sesuai',
                'prioritas' => 'rendah',
                'catatan' => 'Pemeriksaan hash media telah berjalan pada proses unggah.',
                'rekomendasi' => 'Pertahankan pemeriksaan hash dan audit duplikasi setiap bulan.',
                'penanggung_jawab' => 'Admin Sistem',
                'tenggat_tindak_lanjut' => null,
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Kualitas Layanan Informasi',
                'indikator' => 'Foto dan video publik dapat diakses tanpa kesalahan',
                'target' => 'Minimal 99% media dapat diakses',
                'realisasi' => '99% media dapat diakses',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'sedang',
                'catatan' => 'Akses media melalui IP dan domain berjalan baik setelah pengujian.',
                'rekomendasi' => 'Pertahankan pemeriksaan tautan media secara berkala.',
                'penanggung_jawab' => 'Admin Sistem',
                'tenggat_tindak_lanjut' => '2026-08-10',
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Pencarian dan Retrieval',
                'indikator' => 'Data dapat ditemukan berdasarkan judul, tanggal, dan kata kunci',
                'target' => '100% data terindeks dan dapat ditemukan',
                'realisasi' => '100% data dapat ditemukan',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'rendah',
                'catatan' => 'Pencarian berdasarkan judul, tanggal, dan kata kunci berfungsi dengan baik.',
                'rekomendasi' => 'Pertahankan konsistensi penulisan judul dan metadata kegiatan.',
                'penanggung_jawab' => 'Operator Dokumentasi',
                'tenggat_tindak_lanjut' => '2026-08-20',
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Keamanan dan Backup',
                'indikator' => 'Backup database SIGAP berhasil dijalankan',
                'target' => '100% backup harian berhasil',
                'realisasi' => '100% backup berhasil',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'tinggi',
                'catatan' => 'Backup database tersedia sesuai jadwal pemeriksaan.',
                'rekomendasi' => 'Lakukan uji pemulihan database sekurang-kurangnya setiap bulan.',
                'penanggung_jawab' => 'Admin Sistem',
                'tenggat_tindak_lanjut' => '2026-08-31',
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Keamanan dan Backup',
                'indikator' => 'Seluruh file dokumentasi memiliki salinan cadangan',
                'target' => '100% file memiliki cadangan',
                'realisasi' => '100% file memiliki cadangan',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'sedang',
                'catatan' => 'Seluruh media terkonfirmasi tersedia pada penyimpanan cadangan.',
                'rekomendasi' => 'Pertahankan sinkronisasi cadangan dan lakukan uji pemulihan berkala.',
                'penanggung_jawab' => 'Admin Sistem',
                'tenggat_tindak_lanjut' => '2026-08-15',
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Kualitas Layanan Informasi',
                'indikator' => 'Rilis dipublikasikan setelah verifikasi konten',
                'target' => '100% rilis melalui verifikasi',
                'realisasi' => '100% rilis melalui verifikasi',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'sedang',
                'catatan' => 'Alur status publikasi telah tersedia dan digunakan.',
                'rekomendasi' => 'Pertahankan pemeriksaan judul, isi, gambar, dan sumber sebelum publikasi.',
                'penanggung_jawab' => 'Admin Konten',
                'tenggat_tindak_lanjut' => null,
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Kualitas Layanan Informasi',
                'indikator' => 'Tidak ada tautan publik yang menghasilkan error 404',
                'target' => '0 tautan rusak',
                'realisasi' => '0 tautan rusak pada hasil pemeriksaan',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'sedang',
                'catatan' => 'Seluruh tautan publik yang diperiksa dapat diakses dengan baik.',
                'rekomendasi' => 'Pertahankan audit tautan dan validasi ketersediaan file secara berkala.',
                'penanggung_jawab' => 'Admin Sistem',
                'tenggat_tindak_lanjut' => '2026-08-10',
                'status_tindak_lanjut' => 'selesai',
            ],
            [
                'aspek' => 'Tindak Lanjut Stakeholder',
                'indikator' => 'Temuan prioritas tinggi diselesaikan sesuai tenggat',
                'target' => 'Minimal 90% selesai tepat waktu',
                'realisasi' => '95% selesai tepat waktu',
                'skor' => 100,
                'status' => 'sesuai',
                'prioritas' => 'sedang',
                'catatan' => 'Temuan prioritas tinggi telah ditindaklanjuti sesuai tenggat.',
                'rekomendasi' => 'Pertahankan rapat monev bulanan dan dokumentasi bukti penyelesaian.',
                'penanggung_jawab' => 'Kepala Bagian dan Admin',
                'tenggat_tindak_lanjut' => '2026-08-07',
                'status_tindak_lanjut' => 'selesai',
            ],
        ];

        foreach ($items as $item) {
            MonevChecklist::updateOrCreate(
                [
                    'periode' => 'Juli 2026',
                    'aspek' => $item['aspek'],
                    'indikator' => $item['indikator'],
                ],
                [
                    ...$item,
                    'tanggal' => '2026-07-30',
                    'user_id' => $userId,
                ],
            );
        }
    }
}
