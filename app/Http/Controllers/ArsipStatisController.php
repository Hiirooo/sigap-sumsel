<?php

namespace App\Http\Controllers;

use App\Models\ArsipStatis;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
            ->with('anggota')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhere('asal_dokumen', 'like', "%{$search}%")
                        ->orWhereHas('anggota', function ($query) use ($search) {
                            $query->where('nama', 'like', "%{$search}%")
                                ->orWhere('nip', 'like', "%{$search}%");
                        });
                });
            })
            ->when($type, fn ($query, $type) => $query->where('jenis_asli', $type))
            ->when($from, fn ($query, $date) => $query->whereDate('tanggal_asli', '>=', $date))
            ->when($until, fn ($query, $date) => $query->whereDate('tanggal_asli', '<=', $date))
            ->get()
            ->map(function (ArsipStatis $item) {
                $detail = $this->decodeDetail($item->deskripsi);
                $anggota = $this->resolveAnggota($item, $detail);
                $kolektif = (bool) $item->is_kolektif || count($anggota) > 1;

                return [
                    'key' => 'arsip-kepegawaian-'.$item->id,
                    'id' => $item->id,
                    'tanggal_masuk' => $item->created_at?->toDateString(),
                    'tanggal_asli' => $item->tanggal_asli,
                    'nama' => implode(', ', array_column($anggota, 'nama')),
                    'nip' => implode(', ', array_filter(array_column($anggota, 'nip'))),
                    'anggota' => $anggota,
                    'kolektif' => $kolektif,
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
        Log::info('ArsipStatis store called', [
            'method' => $request->method(),
            'url' => $request->url(),
            'has_file' => $request->hasFile('file_digital'),
            'all_keys' => array_keys($request->except(['_token', '_method'])),
            'is_inertia' => $request->header('X-Inertia'),
        ]);

        $kolektifRaw = $request->input('kolektif');
        $request->merge(['kolektif' => in_array($kolektifRaw, [true, 'true', '1', 1], true) ? '1' : '0']);

        $validator = Validator::make($request->all(), $this->rules(true, $request));

        if ($validator->fails()) {
            Log::error('ArsipStatis validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->except(['file_digital']),
                'kolektif_value' => $request->input('kolektif'),
                'kolektif_type' => gettype($request->input('kolektif')),
            ]);
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        try {
            $anggota = $this->collectAnggota($validated);
            $data = $this->formatArchiveData($validated, $anggota);

            if ($request->hasFile('file_digital')) {
                $data['file_path'] = $request->file('file_digital')->store('uploads/arsip');
            }

            $arsip = ArsipStatis::create($data);
            $arsip->anggota()->createMany($anggota);
        } catch (\Throwable $e) {
            Log::error('ARsipStatis store failed: '.$e->getMessage(), [
                'exception' => $e,
                'validated' => $validated ?? null,
                'has_file' => $request->hasFile('file_digital'),
            ]);
            return redirect()->back()->with('error', 'Gagal menyimpan arsip. Silakan periksa kembali data Anda.');
        }

        return redirect()->route('arsip-statis.index')->with('success', 'Arsip Kepegawaian berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $arsip = ArsipStatis::with('anggota')->findOrFail($id);

        $detail = $this->decodeDetail($arsip->deskripsi);
        $anggota = $this->resolveAnggota($arsip, $detail);

        return Inertia::render('ArsipStatis/Edit', [
            'arsip' => [
                'id' => $arsip->id,
                'jenis_asli' => $arsip->jenis_asli,
                'tanggal_asli' => $arsip->tanggal_asli,
                'kolektif' => (bool) $arsip->is_kolektif || count($anggota) > 1,
                'anggota' => $anggota,
                'nama' => $anggota[0]['nama'] ?? '',
                'nip' => $anggota[0]['nip'] ?? '',
                'file_url' => $arsip->file_url,
                ...$detail,
            ],
            'jenisOptions' => $this->archiveTypes(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $arsip = ArsipStatis::findOrFail($id);

        $kolektifRaw = $request->input('kolektif');
        $request->merge(['kolektif' => in_array($kolektifRaw, [true, 'true', '1', 1], true) ? '1' : '0']);

        $validated = $request->validate($this->rules(false, $request));

        try {
            $anggota = $this->collectAnggota($validated);
            $data = $this->formatArchiveData($validated, $anggota);

            if ($request->hasFile('file_digital')) {
                if ($arsip->file_path) {
                    $this->deleteStoredFile($arsip->file_path);
                }
                $data['file_path'] = $request->file('file_digital')->store('uploads/arsip');
            }

            $arsip->update($data);
            $arsip->anggota()->delete();
            $arsip->anggota()->createMany($anggota);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui arsip. Silakan periksa kembali data Anda.');
        }

        return redirect()->route('arsip-statis.index')->with('success', 'Arsip Kepegawaian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        try {
            $arsip = ArsipStatis::findOrFail($id);

            if ($arsip->file_path) {
                $this->deleteStoredFile($arsip->file_path);
            }

            $arsip->delete();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menghapus arsip. Silakan coba lagi.');
        }

        return redirect()->route('arsip-statis.index')->with('success', 'Arsip Kepegawaian berhasil dihapus.');
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

    private function rules(bool $fileRequired, Request $request): array
    {
        $kolektif = filter_var($request->input('kolektif', false), FILTER_VALIDATE_BOOLEAN);

        return [
            'jenis_asli' => ['required', Rule::in(array_keys($this->archiveTypes()))],
            'kode_klasifikasi_surat' => 'required|string|max:255',
            'nomor_nota_dinas' => 'required|string|max:255',
            'tanggal_asli' => 'required|date',
            'kolektif' => 'sometimes|in:0,1',
            'nama' => 'required_unless:kolektif,1|nullable|string|max:255',
            'nip' => 'required_unless:kolektif,1|nullable|string|max:255',
            'anggota' => $kolektif ? 'required|array|min:2' : 'sometimes|array',
            'anggota.*.nama' => $kolektif ? 'required|string|max:255' : 'nullable|string|max:255',
            'anggota.*.nip' => 'nullable|string|max:255',
            'perihal' => 'required|string|max:255',
            'tujuan' => 'required|string|max:255',
            'no_surat_cuti' => 'required_if:jenis_asli,cuti|nullable|string|max:255',
            'file_digital' => ($fileRequired ? 'required' : 'nullable').'|file|mimes:pdf,zip,rar|max:51200',
        ];
    }

    private function collectAnggota(array $validated): array
    {
        $kolektif = filter_var($validated['kolektif'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $anggota = $kolektif
            ? ($validated['anggota'] ?? [])
            : [['nama' => $validated['nama'], 'nip' => $validated['nip'] ?? null]];

        return array_values(array_map(fn (array $item) => [
            'nama' => $item['nama'],
            'nip' => $item['nip'] ?? null,
        ], $anggota));
    }

    private function resolveAnggota(ArsipStatis $arsip, array $detail): array
    {
        $anggota = $arsip->anggota
            ->map(fn ($a) => ['nama' => $a->nama, 'nip' => $a->nip])
            ->values()
            ->all();

        if (empty($anggota)) {
            $anggota = [['nama' => $detail['nama'] ?? $arsip->judul, 'nip' => $detail['nip'] ?? null]];
        }

        return $anggota;
    }

    private function formatArchiveData(array $validated, array $anggota): array
    {
        $detail = [
            'kode_klasifikasi_surat' => $validated['kode_klasifikasi_surat'],
            'nomor_nota_dinas' => $validated['nomor_nota_dinas'],
            'perihal' => $validated['perihal'],
            'tujuan' => $validated['tujuan'],
            'no_surat_cuti' => $validated['no_surat_cuti'] ?? null,
        ];

        return [
            'judul' => $anggota[0]['nama'] ?? $validated['nama'] ?? 'Kolektif',
            'deskripsi' => json_encode($detail),
            'asal_dokumen' => $validated['tujuan'],
            'tanggal_asli' => $validated['tanggal_asli'],
            'jenis_asli' => $validated['jenis_asli'],
            'is_kolektif' => count($anggota) > 1,
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
