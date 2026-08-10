<?php

namespace App\Http\Controllers;

use App\Models\Kliping;
use App\Services\ArticleSentimentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class KlipingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'sentimen', 'status', 'arsip', 'tanggal_mulai', 'tanggal_selesai', 'per_page']);
        $selectedStatus = in_array($filters['status'] ?? null, ['draft', 'terpublikasi'], true)
            ? $filters['status']
            : 'draft';
        $filters['status'] = $selectedStatus;
        $filters['arsip'] = in_array($filters['arsip'] ?? null, ['belum', 'sudah', 'semua'], true)
            ? $filters['arsip']
            : 'belum';
        $archiveFilter = $filters['arsip'];
        $statusCounts = [
            'draft' => Kliping::where('status', 'draft')->when($archiveFilter !== 'semua', fn ($query) => $query->where('is_archived', $archiveFilter === 'sudah'))->count(),
            'terpublikasi' => Kliping::where('status', 'terpublikasi')->when($archiveFilter !== 'semua', fn ($query) => $query->where('is_archived', $archiveFilter === 'sudah'))->count(),
        ];

        $query = Kliping::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('media', 'like', "%{$search}%");
                });
            })
            ->when($filters['sentimen'] ?? null, fn ($query, $sentimen) => $query->where('sentimen', $sentimen))
            ->where('status', $selectedStatus)
            ->when($archiveFilter !== 'semua', fn ($query) => $query->where('is_archived', $archiveFilter === 'sudah'))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
            ->latest();

        $requestedPerPage = (string) ($filters['per_page'] ?? '10');
        $perPage = $requestedPerPage === 'all'
            ? max(1, (clone $query)->count())
            : (ctype_digit($requestedPerPage) ? min(1000, max(1, (int) $requestedPerPage)) : 10);
        $filters['per_page'] = $requestedPerPage === 'all' ? 'all' : (string) $perPage;

        $kliping = $query->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Kliping/Index', [
            'kliping' => $kliping,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Kliping/Create');
    }

    public function store(Request $request, ArticleSentimentService $sentimentService)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'media' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'url' => 'nullable|url|max:2048',
            'gambar_url' => 'nullable|url|max:2048',
            'isi_berita' => 'nullable|string',
            'sentimen' => 'required|in:positif,netral,negatif',
            'status' => 'required|in:draft,terpublikasi',
            'sentimen_confidence' => 'nullable|integer|min:0|max:100',
            'sentimen_otomatis' => 'nullable|boolean',
            'sentimen_metode' => 'nullable|string|max:50',
            'sentimen_model' => 'nullable|string|max:100',
            'terkait_pimpinan' => 'nullable|boolean',
            'persentase_keterkaitan' => 'nullable|integer|min:0|max:100',
            'tingkat_keterkaitan' => 'nullable|string|max:30',
            'kata_kunci_keterkaitan' => 'nullable|string|max:255',
            'file' => 'nullable|required_without:url|file|mimes:pdf,jpeg,jpg,png|max:51200',
        ]);

        unset($validated['gambar_url']);
        $validated['sentimen_otomatis'] = $request->boolean('sentimen_otomatis');
        $validated['terkait_pimpinan'] = $request->boolean('terkait_pimpinan');
        $this->ensureRelevantDetectedArticle($validated);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('uploads/kliping', $this->storageDisk());
        } elseif ($request->filled('gambar_url')) {
            $validated['file_path'] = $sentimentService->downloadImage($request->string('gambar_url')->toString());
        }

        Kliping::create($validated);

        return redirect()->route('kliping.index')->with('message', 'Kliping berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return Inertia::render('Kliping/Edit', [
            'kliping' => Kliping::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id, ArticleSentimentService $sentimentService)
    {
        $kliping = Kliping::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'media' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'url' => 'nullable|url|max:2048',
            'gambar_url' => 'nullable|url|max:2048',
            'isi_berita' => 'nullable|string',
            'sentimen' => 'required|in:positif,netral,negatif',
            'status' => 'required|in:draft,terpublikasi',
            'sentimen_confidence' => 'nullable|integer|min:0|max:100',
            'sentimen_otomatis' => 'nullable|boolean',
            'sentimen_metode' => 'nullable|string|max:50',
            'sentimen_model' => 'nullable|string|max:100',
            'terkait_pimpinan' => 'nullable|boolean',
            'persentase_keterkaitan' => 'nullable|integer|min:0|max:100',
            'tingkat_keterkaitan' => 'nullable|string|max:30',
            'kata_kunci_keterkaitan' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:51200',
        ]);

        unset($validated['gambar_url']);
        $validated['sentimen_otomatis'] = $request->boolean('sentimen_otomatis');
        $validated['terkait_pimpinan'] = $request->boolean('terkait_pimpinan');
        $this->ensureRelevantDetectedArticle($validated);

        if ($request->hasFile('file')) {
            if ($kliping->file_path) {
                $this->deleteStoredFile($kliping->file_path);
            }

            $validated['file_path'] = $request->file('file')->store('uploads/kliping', $this->storageDisk());
        } elseif (! $kliping->file_path && $request->filled('gambar_url')) {
            $validated['file_path'] = $sentimentService->downloadImage($request->string('gambar_url')->toString());
        }

        $kliping->update($validated);

        return redirect()->route('kliping.index')->with('message', 'Kliping berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kliping = Kliping::findOrFail($id);

        if ($kliping->file_path) {
            $this->deleteStoredFile($kliping->file_path);
        }

        $kliping->delete();

        return redirect()->route('kliping.index')->with('message', 'Kliping berhasil dihapus.');
    }

    public function toggleStatus(Kliping $kliping)
    {
        $nextStatus = match ($kliping->status) {
            'draft' => 'terpublikasi',
            default => 'draft',
        };

        $kliping->update(['status' => $nextStatus]);

        return back()->with('message', "Status kliping diubah menjadi {$nextStatus}.");
    }

    public function toggleArchive(Kliping $kliping)
    {
        $kliping->update(['is_archived' => ! $kliping->is_archived]);

        return back()->with('message', $kliping->is_archived
            ? 'Kliping berhasil diarsipkan.'
            : 'Kliping dikeluarkan dari arsip.');
    }

    public function detectUrl(Request $request, ArticleSentimentService $sentimentService)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        try {
            $result = $sentimentService->analyzeUrl($validated['url']);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }

    public function importUrl(Request $request, ArticleSentimentService $sentimentService)
    {
        $validated = $request->validate([
            'url' => 'required|url:http,https|max:2048',
            'status' => 'required|in:draft,terpublikasi',
        ]);
        $url = rtrim($validated['url'], '/');
        $existing = Kliping::where('url', $url)->first();

        if ($existing) {
            return response()->json([
                'status' => 'duplicate',
                'id' => $existing->id,
                'title' => $existing->judul,
                'message' => 'URL sudah tersedia di kliping.',
            ]);
        }

        $downloadedPath = null;
        try {
            $result = $sentimentService->analyzeUrl($url);
            $data = [
                'judul' => $result['title'] ?? 'Berita Online',
                'media' => $result['media'] ?? (parse_url($url, PHP_URL_HOST) ?: 'Media Online'),
                'tanggal' => $result['published_at'] ?? now()->toDateString(),
                'url' => $url,
                'isi_berita' => $result['content'] ?? null,
                'sentimen' => $result['sentimen'] ?? 'netral',
                'status' => $validated['status'],
                'sentimen_confidence' => $result['confidence'] ?? null,
                'sentimen_otomatis' => true,
                'sentimen_metode' => $result['sentimen_metode'] ?? null,
                'sentimen_model' => $result['sentimen_model'] ?? null,
                'terkait_pimpinan' => (bool) ($result['terkait_pimpinan'] ?? false),
                'persentase_keterkaitan' => $result['persentase_keterkaitan'] ?? null,
                'tingkat_keterkaitan' => $result['tingkat_keterkaitan'] ?? null,
                'kata_kunci_keterkaitan' => $result['kata_kunci_keterkaitan'] ?? null,
            ];
            $this->ensureRelevantDetectedArticle($data);

            if (! empty($result['image_url'])) {
                try {
                    $downloadedPath = $sentimentService->downloadImage($result['image_url']);
                    $data['file_path'] = $downloadedPath;
                } catch (\Throwable) {
                    // Gambar bersifat opsional; artikel tetap dapat diimpor.
                }
            }

            $kliping = Kliping::create($data);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => collect($exception->errors())->flatten()->first() ?: 'Artikel tidak dapat diimpor.',
            ], 422);
        } catch (\Throwable $exception) {
            if ($downloadedPath) {
                $this->deleteStoredFile($downloadedPath);
            }

            return response()->json([
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'created',
            'id' => $kliping->id,
            'title' => $kliping->judul,
            'message' => 'Kliping berhasil diimpor.',
        ], 201);
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

    private function storageDisk(): string
    {
        $disk = (string) config('services.kliping.storage_disk', 'local');

        return in_array($disk, ['local', 'google-drive'], true) ? $disk : 'local';
    }

    private function ensureRelevantDetectedArticle(array $data): void
    {
        $score = array_key_exists('persentase_keterkaitan', $data) && $data['persentase_keterkaitan'] !== null
            ? (int) $data['persentase_keterkaitan']
            : (($data['terkait_pimpinan'] ?? false) ? 100 : 0);

        if (($data['sentimen_otomatis'] ?? false) && filled($data['isi_berita'] ?? null) && (! ($data['terkait_pimpinan'] ?? false) || $score < 50)) {
            throw ValidationException::withMessages([
                'url' => 'Berita tidak cukup terkait dengan Gubernur, Wakil Gubernur, Sekda/Sekretaris Daerah, Pemprov Sumsel, atau kegiatan pimpinan lainnya.',
            ]);
        }
    }
}
