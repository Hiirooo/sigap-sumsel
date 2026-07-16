<!DOCTYPE html>
<html>
<head>
    <title>Laporan Monev SIGAP SUMSEL</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0c2d5e; padding-bottom: 10px; margin-bottom: 16px; }
        h2 { margin: 0; color: #0c2d5e; text-transform: uppercase; }
        .summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .summary td { border: 1px solid #ddd; padding: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #0c2d5e; color: white; }
        .footer { margin-top: 32px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Monitoring dan Evaluasi</h2>
        <p>SIGAP SUMSEL - Biro Humas dan Protokol Provinsi Sumatera Selatan</p>
        <p>Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <table class="summary">
        <tr>
            <td><strong>Total Checklist</strong><br>{{ $summary['total'] }}</td>
            <td><strong>Rata-rata Skor</strong><br>{{ $summary['average_score'] }}%</td>
            <td><strong>Tindak Lanjut Selesai</strong><br>{{ $summary['completion_rate'] }}%</td>
            <td><strong>Item Kritis</strong><br>{{ $summary['critical_count'] }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Aspek</th>
                <th>Indikator</th>
                <th>Skor</th>
                <th>Status</th>
                <th>Prioritas</th>
                <th>Rekomendasi</th>
                <th>Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ optional($item->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $item->aspek }}</td>
                    <td>{{ $item->indikator }}</td>
                    <td>{{ $item->skor }}%</td>
                    <td>{{ str_replace('_', ' ', $item->status) }}</td>
                    <td>{{ $item->prioritas }}</td>
                    <td>{{ $item->rekomendasi ?: '-' }}</td>
                    <td>{{ $item->status_tindak_lanjut }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">Belum ada data monev.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Palembang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
        <br><br><br>
        <p><strong>( ______________________ )</strong></p>
    </div>
</body>
</html>
