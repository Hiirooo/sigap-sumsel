<?php

namespace App\Http\Controllers;

use App\Models\MonevChecklist;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonevChecklistController extends Controller
{
    public function index(Request $request)
    {
        [$items, $summary, $filters] = $this->buildReport($request);

        return Inertia::render('Monev/Index', [
            'items' => $items,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Monev/Create');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['user_id'] = $request->user()->id;

        MonevChecklist::create($data);

        return redirect()->route('monev.index')->with('message', 'Checklist monev berhasil ditambahkan.');
    }

    public function edit(MonevChecklist $monev)
    {
        return Inertia::render('Monev/Edit', [
            'item' => $monev,
        ]);
    }

    public function update(Request $request, MonevChecklist $monev)
    {
        $monev->update($this->validateRequest($request));

        return redirect()->route('monev.index')->with('message', 'Checklist monev berhasil diperbarui.');
    }

    public function destroy(MonevChecklist $monev)
    {
        $monev->delete();

        return redirect()->route('monev.index')->with('message', 'Checklist monev berhasil dihapus.');
    }

    public function cetakPdf(Request $request)
    {
        [$items, $summary, $filters] = $this->buildReport($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('monev-pdf', compact('items', 'summary', 'filters'));

        return $pdf->download('laporan-monev-sigap-sumsel.pdf');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'tanggal' => 'required|date',
            'periode' => 'nullable|string|max:255',
            'aspek' => 'required|string|max:255',
            'indikator' => 'required|string|max:255',
            'target' => 'nullable|string|max:255',
            'realisasi' => 'nullable|string|max:255',
            'skor' => 'required|integer|min:0|max:100',
            'status' => 'required|in:sesuai,perlu_perhatian,kritis',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'catatan' => 'nullable|string',
            'rekomendasi' => 'nullable|string',
            'penanggung_jawab' => 'nullable|string|max:255',
            'tenggat_tindak_lanjut' => 'nullable|date',
            'status_tindak_lanjut' => 'required|in:belum,proses,selesai',
        ]);
    }

    private function buildReport(Request $request): array
    {
        $filters = $request->only(['search', 'status', 'prioritas', 'tanggal_mulai', 'tanggal_selesai']);

        $query = MonevChecklist::with('user')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('aspek', 'like', "%{$search}%")
                        ->orWhere('indikator', 'like', "%{$search}%")
                        ->orWhere('penanggung_jawab', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['prioritas'] ?? null, fn ($query, $priority) => $query->where('prioritas', $priority))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date));

        $items = $query->latest('tanggal')->latest()->get();
        $total = $items->count();
        $averageScore = $total > 0 ? round($items->avg('skor')) : 0;

        $summary = [
            'total' => $total,
            'average_score' => $averageScore,
            'completion_rate' => $total > 0 ? round(($items->where('status_tindak_lanjut', 'selesai')->count() / $total) * 100) : 0,
            'critical_count' => $items->where('status', 'kritis')->count(),
            'high_priority_count' => $items->where('prioritas', 'tinggi')->count(),
            'status' => [
                ['label' => 'Sesuai', 'value' => $items->where('status', 'sesuai')->count(), 'color' => '#16a34a'],
                ['label' => 'Perlu Perhatian', 'value' => $items->where('status', 'perlu_perhatian')->count(), 'color' => '#d4af37'],
                ['label' => 'Kritis', 'value' => $items->where('status', 'kritis')->count(), 'color' => '#dc2626'],
            ],
            'follow_up' => [
                ['label' => 'Belum', 'value' => $items->where('status_tindak_lanjut', 'belum')->count(), 'color' => '#dc2626'],
                ['label' => 'Proses', 'value' => $items->where('status_tindak_lanjut', 'proses')->count(), 'color' => '#d4af37'],
                ['label' => 'Selesai', 'value' => $items->where('status_tindak_lanjut', 'selesai')->count(), 'color' => '#16a34a'],
            ],
        ];

        return [$items, $summary, $filters];
    }
}
