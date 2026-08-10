<?php

namespace App\Http\Controllers;

use App\Models\ArsipStatis;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ArsipStatisController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'jenis_asli', 'tanggal_mulai', 'tanggal_selesai']);

        $type = $filters['jenis_asli'] ?? null;
        $search = $filters['search'] ?? null;
        $from = $filters['tanggal_mulai'] ?? null;
        $until = $filters['tanggal_selesai'] ?? null;
        $arsip = ArsipStatis::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhere('asal_dokumen', 'like', "%{$search}%");
                });
            })
            ->when($type, fn ($query, $type) => $query->where('jenis_asli', $type))
            ->when($from, fn ($query, $date) => $query->whereDate('tanggal_asli', '>=', $date))
            ->when($until, fn ($query, $date) => $query->whereDate('tanggal_asli', '<=', $date))
            ->get()
            ->map(fn (ArsipStatis $item) => [
                'key' => 'arsip-kepegawaian-'.$item->id,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'tanggal_asli' => $item->tanggal_asli,
                'asal_dokumen' => $item->asal_dokumen,
                'jenis_asli' => $item->jenis_asli,
                'file_url' => $item->file_url,
                'edit_url' => route('arsip-statis.edit', $item),
                'delete_url' => route('arsip-statis.destroy', $item),
                'sort_date' => $item->tanggal_asli ?? $item->created_at?->toDateString(),
            ])
            ->sortByDesc('sort_date')
            ->values()
            ->map(function (array $item) {
            unset($item['sort_date']);
            return $item;
        });

        return Inertia::render('ArsipStatis/Index', [
            'arsip' => $arsip,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('ArsipStatis/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'asal_dokumen' => 'nullable|string|max:255',
            'tanggal_asli' => 'nullable|date',
            'jenis_asli' => 'required|in:fisik,cetak,cd,lainnya',
            'file_digital' => 'required|file|mimes:pdf,zip,rar|max:51200', 
        ]);

        if ($request->hasFile('file_digital')) {
            $validated['file_path'] = $request->file('file_digital')->store('uploads/arsip');
        }

        ArsipStatis::create($validated);

        return redirect()->route('arsip-statis.index')->with('message', 'Arsip Kepegawaian berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $arsip = ArsipStatis::findOrFail($id);
        return Inertia::render('ArsipStatis/Edit', [
            'arsip' => $arsip
        ]);
    }

    public function update(Request $request, $id)
    {
        $arsip = ArsipStatis::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'asal_dokumen' => 'nullable|string|max:255',
            'tanggal_asli' => 'nullable|date',
            'jenis_asli' => 'required|in:fisik,cetak,cd,lainnya',
            'file_digital' => 'nullable|file|mimes:pdf,zip,rar|max:51200', 
        ]);

        if ($request->hasFile('file_digital')) {
            if ($arsip->file_path) {
                $this->deleteStoredFile($arsip->file_path);
            }
            $validated['file_path'] = $request->file('file_digital')->store('uploads/arsip');
        }

        $arsip->update($validated);

        return redirect()->route('arsip-statis.index')->with('message', 'Arsip Kepegawaian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $arsip = ArsipStatis::findOrFail($id);
        
        if ($arsip->file_path) {
            $this->deleteStoredFile($arsip->file_path);
        }
        
        $arsip->delete();

        return redirect()->route('arsip-statis.index')->with('message', 'Arsip Kepegawaian berhasil dihapus.');
    }

    private function deleteStoredFile(string $path): void
    {
        $path = str_starts_with($path, '/storage/')
            ? ltrim(str_replace('/storage/', '', $path), '/')
            : (str_starts_with($path, 'public/') ? substr($path, 7) : $path);

        foreach (['google-drive', 'local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                return;
            }
        }
    }
}
