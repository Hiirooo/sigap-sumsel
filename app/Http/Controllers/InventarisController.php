<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class InventarisController extends Controller
{
    public function index(Request $request)
    {
        [$stats, $items, $filters] = $this->buildInventory($request);
        
        return Inertia::render('Inventaris/Index', [
            'stats' => $stats,
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function cetakPdf(Request $request)
    {
        [$stats, $items, $filters] = $this->buildInventory($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan-pdf', compact('stats', 'items', 'filters'));
        return $pdf->download('laporan-rekapitulasi-sigap-sumsel.pdf');
    }

    private function buildInventory(Request $request): array
    {
        $filters = $request->only(['search', 'jenis_dokumen', 'tanggal_mulai', 'tanggal_selesai']);

        $stats = [
            'rilis_berita' => \App\Models\RilisBerita::count(),
            'dokumentasi' => \App\Models\Dokumentasi::count(),
            'arsip_statis' => \App\Models\ArsipStatis::count(),
            'kliping' => \App\Models\Kliping::count(),
            'kategori' => \App\Models\KategoriKegiatan::count(),
            'logs' => \App\Models\LogAktivitas::count()
        ];

        $items = collect();

        if (! ($filters['jenis_dokumen'] ?? null) || $filters['jenis_dokumen'] === 'rilis_berita') {
            $items = $items->merge(
                \App\Models\RilisBerita::query()
                    ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('judul', 'like', "%{$search}%"))
                    ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_rilis', '>=', $date))
                    ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_rilis', '<=', $date))
                    ->get()
                    ->map(fn ($item) => [
                        'jenis' => 'Rilis Berita',
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal_rilis,
                        'status' => $item->status,
                        'sumber' => $item->media_publikasi ?: $item->penulis,
                    ])
            );
        }

        if (! ($filters['jenis_dokumen'] ?? null) || $filters['jenis_dokumen'] === 'dokumentasi') {
            $items = $items->merge(
                \App\Models\Dokumentasi::query()
                    ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('judul', 'like', "%{$search}%"))
                    ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
                    ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
                    ->get()
                    ->map(fn ($item) => [
                        'jenis' => 'Dokumentasi ' . ucfirst($item->jenis_media),
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal,
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
                        'jenis' => 'Arsip Statis',
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal_asli,
                        'status' => $item->jenis_asli,
                        'sumber' => $item->asal_dokumen,
                    ])
            );
        }

        if (! ($filters['jenis_dokumen'] ?? null) || $filters['jenis_dokumen'] === 'kliping') {
            $items = $items->merge(
                \App\Models\Kliping::query()
                    ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('judul', 'like', "%{$search}%"))
                    ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
                    ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
                    ->get()
                    ->map(fn ($item) => [
                        'jenis' => 'Kliping',
                        'judul' => $item->judul,
                        'tanggal' => $item->tanggal,
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
