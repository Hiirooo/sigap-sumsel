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
        @page {
            margin: 20mm 20mm 25mm 20mm;
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
                <td>Arsip Kepegawaian</td>
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
                <th style="width: 16%;">Jenis</th>
                <th style="width: 34%;">Judul</th>
                <th style="width: 14%;">Tanggal</th>
                <th style="width: 14%;">Status</th>
                <th style="width: 22%;">Sumber/Pimpinan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td style="white-space: nowrap;">{{ $item['jenis'] }}</td>
                    <td>{{ $item['judul'] }}</td>
                    <td style="white-space: nowrap;">{{ $item['tanggal'] ?: '-' }}</td>
                    <td style="white-space: nowrap;">{{ $item['status'] ?: '-' }}</td>
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

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('Times-Roman', 'normal');
            $size = 8;
            $warna = [0.4, 0.4, 0.4];
            $y = $pdf->get_height() - 14;
            $pw = $pdf->get_width();

            $kiri = "Laporan Rekapitulasi Data Aplikasi";
            $tgl = \Carbon\Carbon::now('Asia/Jakarta')->format('d/m/Y H:i');
            $tengah = "Tanggal Cetak: {$tgl} WIB";
            $kanan = "Halaman {PAGE_NUM} / {PAGE_COUNT}";

            $pdf->page_text(20, $y, $kiri, $font, $size, $warna);

            $lebarTengah = $fontMetrics->getTextWidth($tengah, $font, $size);
            $pdf->page_text(($pw - $lebarTengah) / 2, $y, $tengah, $font, $size, $warna);

            $lebarKanan = $fontMetrics->getTextWidth('Halaman 99 / 99', $font, $size);
            $pdf->page_text($pw - $lebarKanan - 20, $y, $kanan, $font, $size, $warna);
        }
    </script>

</body>
</html>
