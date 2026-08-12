<?php

namespace App\Http\Controllers;

use App\Models\ArsipStatis;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
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
            ->map(function (ArsipStatis $item) {
                $detail = $this->decodeDetail($item->deskripsi);

                return [
                    'key' => 'arsip-kepegawaian-'.$item->id,
                    'id' => $item->id,
                    'tanggal_masuk' => $item->created_at?->toDateString(),
                    'tanggal_asli' => $item->tanggal_asli,
                    'nama' => $detail['nama'] ?? $item->judul,
                    'nip' => $detail['nip'] ?? null,
                    'perihal' => $detail['perihal'] ?? null,
                    'jenis_asli' => $item->jenis_asli,
                    'jenis_label' => $this->archiveTypes()[$item->jenis_asli] ?? $item->jenis_asli,
                    'file_url' => $item->file_url,
                    'edit_url' => route('arsip-statis.edit', $item),
                    'delete_url' => route('arsip-statis.destroy', $item),
                    'sort_date' => $item->created_at?->toDateString(),
                ];
            })
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
        return Inertia::render('ArsipStatis/Create', [
            'jenisOptions' => $this->archiveTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(true));

        $validated = $this->formatArchiveData($validated);

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
            'arsip' => [
                'id' => $arsip->id,
                'jenis_asli' => $arsip->jenis_asli,
                'tanggal_asli' => $arsip->tanggal_asli,
                'file_url' => $arsip->file_url,
                ...$this->decodeDetail($arsip->deskripsi),
            ],
            'jenisOptions' => $this->archiveTypes(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $arsip = ArsipStatis::findOrFail($id);

        $validated = $request->validate($this->rules(false));

        $validated = $this->formatArchiveData($validated);

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

    private function rules(bool $fileRequired): array
    {
        return [
            'jenis_asli' => ['required', Rule::in(array_keys($this->archiveTypes()))],
            'kode_klasifikasi_surat' => 'required|string|max:255',
            'nomor_nota_dinas' => 'required|string|max:255',
            'tanggal_asli' => 'required|date',
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'tujuan' => 'required|string|max:255',
            'no_surat_cuti' => 'required_if:jenis_asli,cuti|nullable|string|max:255',
            'file_digital' => ($fileRequired ? 'required' : 'nullable').'|file|mimes:pdf,zip,rar|max:51200',
        ];
    }

    private function formatArchiveData(array $validated): array
    {
        $detail = [
            'kode_klasifikasi_surat' => $validated['kode_klasifikasi_surat'],
            'nomor_nota_dinas' => $validated['nomor_nota_dinas'],
            'nama' => $validated['nama'],
            'nip' => $validated['nip'],
            'perihal' => $validated['perihal'],
            'tujuan' => $validated['tujuan'],
            'no_surat_cuti' => $validated['no_surat_cuti'] ?? null,
        ];

        return [
            'judul' => $validated['nama'],
            'deskripsi' => json_encode($detail),
            'asal_dokumen' => $validated['tujuan'],
            'tanggal_asli' => $validated['tanggal_asli'],
            'jenis_asli' => $validated['jenis_asli'],
        ];
    }

    private function decodeDetail(?string $description): array
    {
        if (! $description) {
            return [];
        }

        $decoded = json_decode($description, true);

        return is_array($decoded) ? $decoded : ['perihal' => $description];
    }

    private function archiveTypes(): array
    {
        return [
            'cuti' => 'Cuti',
            'kenaikan_pangkat' => 'Kenaikan Pangkat',
            'berkala' => 'Berkala',
        ];
    }
}
