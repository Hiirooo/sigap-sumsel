<!DOCTYPE html>
<html>
<head>
    <title>Laporan Rekapitulasi SIGAP SUMSEL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0c2d5e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            color: #0c2d5e;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #555;
        }
        .summary-box {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .summary-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            color: #0c2d5e;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .stats-table th, .stats-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .stats-table th {
            background-color: #0c2d5e;
            color: white;
        }
        .muted {
            color: #666;
            font-size: 11px;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
            font-size: 12px;
        }
        .signature-area {
            margin-top: 50px;
            display: inline-block;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Biro Humas dan Protokol</h2>
        <h2>Provinsi Sumatera Selatan</h2>
        <p>Sistem Informasi Galeri Pimpinan (SIGAP SUMSEL)</p>
    </div>

    <div class="summary-box">
        <div class="summary-title">Laporan Rekapitulasi Data Aplikasi</div>
        <p>Tanggal Cetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</p>
        <p class="muted">
            Filter:
            Jenis Dokumen {{ $filters['jenis_dokumen'] ?? 'Semua' }},
            Kata Kunci {{ $filters['search'] ?? '-' }},
            Periode {{ $filters['tanggal_mulai'] ?? '-' }} s.d. {{ $filters['tanggal_selesai'] ?? '-' }}
        </p>
    </div>

    <table class="stats-table">
        <thead>
            <tr>
                <th>Modul Sistem</th>
                <th>Total Data Terinput</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Rilis Berita</td>
                <td>{{ $stats['rilis_berita'] }} Berita</td>
            </tr>
            <tr>
                <td>Galeri Dokumentasi (Foto/Video)</td>
                <td>{{ $stats['dokumentasi'] }} Berkas</td>
            </tr>
            <tr>
                <td>Arsip Statis & Alih Media</td>
                <td>{{ $stats['arsip_statis'] }} Dokumen</td>
            </tr>
            <tr>
                <td>Kliping Media</td>
                <td>{{ $stats['kliping'] }} Kliping</td>
            </tr>
            <tr>
                <td>Kategori Kegiatan</td>
                <td>{{ $stats['kategori'] }} Kategori</td>
            </tr>
            <tr>
                <td>Log Aktivitas Sistem</td>
                <td>{{ $stats['logs'] }} Aktivitas Terekam</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-title">Daftar Inventaris Sesuai Filter</div>
    <table class="stats-table">
        <thead>
            <tr>
                <th>Jenis</th>
                <th>Judul</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Sumber/Pimpinan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item['jenis'] }}</td>
                    <td>{{ $item['judul'] }}</td>
                    <td>{{ $item['tanggal'] ?: '-' }}</td>
                    <td>{{ $item['status'] ?: '-' }}</td>
                    <td>{{ $item['sumber'] ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Tidak ada data inventaris sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Palembang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
        <div class="signature-area">
            <p>Kepala Biro Humas dan Protokol</p>
            <br><br><br><br>
            <p><strong>( ______________________ )</strong></p>
        </div>
    </div>

</body>
</html>
