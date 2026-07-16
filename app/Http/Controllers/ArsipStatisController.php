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

        $arsip = ArsipStatis::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhere('asal_dokumen', 'like', "%{$search}%");
                });
            })
            ->when($filters['jenis_asli'] ?? null, fn ($query, $type) => $query->where('jenis_asli', $type))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_asli', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_asli', '<=', $date))
            ->latest()
            ->get();

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

        return redirect()->route('arsip-statis.index')->with('message', 'Arsip Statis berhasil ditambahkan.');
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

        return redirect()->route('arsip-statis.index')->with('message', 'Arsip Statis berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $arsip = ArsipStatis::findOrFail($id);
        
        if ($arsip->file_path) {
            $this->deleteStoredFile($arsip->file_path);
        }
        
        $arsip->delete();

        return redirect()->route('arsip-statis.index')->with('message', 'Arsip Statis berhasil dihapus.');
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
