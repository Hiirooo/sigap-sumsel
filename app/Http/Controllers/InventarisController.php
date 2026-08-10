<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class InventarisController extends Controller
{
    public function index(Request $request)
    {
        [$stats, $items, $filters] = $this->buildInventory($request);

        $perPage = (int) ($filters['per_page'] ?? 15);
        $currentPage = (int) ($request->get('page', 1));
        $total = $items->count();

        $paginated = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage)->values(),
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Inventaris/Index', [
            'stats' => $stats,
            'items' => $paginated,
            'filters' => $filters,
        ]);
    }

    public function cetakPdf(Request $request)
    {
        [$stats, $items, $filters] = $this->buildInventory($request, publishedOnly: true);

        $paperSize = $request->get('ukuran_kertas', 'A4') === 'F4' ? [0, 0, 595.28, 935.43] : 'A4';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan-pdf', compact('stats', 'items', 'filters'))
            ->setPaper($paperSize, 'portrait')
            ->setOption('isPhpEnabled', true);
        return $pdf->download('laporan-rekapitulasi-sigap-sumsel.pdf');
    }

    private function buildInventory(Request $request, bool $publishedOnly = false): array
    {
        $filters = array_merge([
            'search' => null,
            'jenis_dokumen' => null,
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'per_page' => null,
            'bulan' => null,
        ], $request->only(['search', 'jenis_dokumen', 'tanggal_mulai', 'tanggal_selesai', 'per_page', 'bulan']));

        if (!empty($filters['bulan'])) {
            $filters['tanggal_mulai'] = $filters['bulan'] . '-01';
            $filters['tanggal_selesai'] = date('Y-m-t', strtotime($filters['bulan'] . '-01'));
        }

        $rilisQuery = \App\Models\RilisBerita::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('judul', 'like', "%{$search}%"))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_rilis', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_rilis', '<=', $date))
            ->when($publishedOnly, fn ($query) => $query->where('status', 'terpublikasi'));

        $stats = [
            'rilis_berita' => (clone $rilisQuery)->count(),
            'dokumentasi' => \App\Models\Dokumentasi::query()
                ->when($publishedOnly, fn ($q) => $q->where('status_verifikasi', 'terverifikasi'))
                ->count(),
            'arsip_statis' => \App\Models\ArsipStatis::count(),
            'kliping' => \App\Models\Kliping::query()
                ->when($publishedOnly, fn ($q) => $q->where('status', 'terpublikasi'))
                ->count(),
            'kategori' => \App\Models\KategoriKegiatan::count(),
            'logs' => \App\Models\LogAktivitas::count()
        ];

        $items = collect();

        if (! ($filters['jenis_dokumen'] ?? null) || $filters['jenis_dokumen'] === 'rilis_berita') {
            $items = $items->merge(
                (clone $rilisQuery)->get()
                    ->map(fn ($item) => [
                        'jenis' => 'Rilis Berita',
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal_rilis ? \Carbon\Carbon::parse($item->tanggal_rilis)->format('d/m/Y') : null,
                        'status' => $item->status,
                        'sumber' => $item->media_publikasi ?: $item->penulis,
                    ])
            );
        }

        $dokumentasiQuery = \App\Models\Dokumentasi::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('judul', 'like', "%{$search}%"))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
            ->when($publishedOnly, fn ($q) => $q->where('status_verifikasi', 'terverifikasi'));

        if (! ($filters['jenis_dokumen'] ?? null) || $filters['jenis_dokumen'] === 'dokumentasi') {
            $items = $items->merge(
                (clone $dokumentasiQuery)->get()
                    ->map(fn ($item) => [
                        'jenis' => 'Dokumentasi ' . ucfirst($item->jenis_media),
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : null,
                        'status' => $item->status_verifikasi,
                        'sumber' => $item->pimpinan_terkait,
                    ])
            );
        }

        if (! ($filters['jenis_dokumen'] ?? null) || $filters['jenis_dokumen'] === 'arsip_statis') {
            $items = $items->merge(
                \App\Models\ArsipStatis::query()
                    ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('judul', 'like', "%{$search}%"))
                    ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_asli', '>=', $date))
                    ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_asli', '<=', $date))
                    ->get()
                    ->map(fn ($item) => [
                        'jenis' => 'Arsip Kepegawaian',
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal_asli ? \Carbon\Carbon::parse($item->tanggal_asli)->format('d/m/Y') : null,
                        'status' => $item->jenis_asli,
                        'sumber' => $item->asal_dokumen,
                    ])
            );
        }

        $klipingQuery = \App\Models\Kliping::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('judul', 'like', "%{$search}%"))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
            ->when($publishedOnly, fn ($q) => $q->where('status', 'terpublikasi'));

        if (! ($filters['jenis_dokumen'] ?? null) || $filters['jenis_dokumen'] === 'kliping') {
            $items = $items->merge(
                (clone $klipingQuery)->get()
                    ->map(fn ($item) => [
                        'jenis' => 'Kliping',
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : null,
                        'status' => $item->sentimen,
                        'sumber' => $item->media,
                    ])
            );
        }

        $items = $items
            ->sortByDesc(fn ($item) => $item['tanggal'] ?: '')
            ->values();

        return [$stats, $items, $filters];
    }
}
