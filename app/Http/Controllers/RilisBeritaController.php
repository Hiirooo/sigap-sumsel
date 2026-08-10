<?php

namespace App\Http\Controllers;

use App\Models\RilisBerita;
use App\Services\SumselprovNewsImporter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class RilisBeritaController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'tanggal_mulai', 'tanggal_selesai']);

        $rilisBerita = RilisBerita::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('isi', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('media_publikasi', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_rilis', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal_rilis', '<=', $date))
            ->latest('tanggal_rilis')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('RilisBerita/Index', [
            'rilisBerita' => $rilisBerita,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('RilisBerita/Create');
    }

    public function previewUrl(Request $request, SumselprovNewsImporter $importer)
    {
        $validated = $request->validate([
            'url' => 'required|url:https|max:2048',
        ]);

        try {
            return response()->json($importer->previewUrl($validated['url']));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception instanceof \InvalidArgumentException
                    ? $exception->getMessage()
                    : 'Data berita dari tautan tersebut tidak dapat diambil.',
            ], 422);
        }
    }

    public function syncSumselprov(Request $request, SumselprovNewsImporter $importer)
    {
        $endpoint = $request->input('endpoint');

        if ($request->input('mode') === 'preview') {
            $validated = $request->validate(['page' => 'required|integer|min:1|max:33']);

            try {
                $result = $importer->previewPage((int) $validated['page'], $endpoint);
            } catch (\Throwable $exception) {
                report($exception);

                return response()->json(['message' => 'Daftar berita Sumselprov gagal diambil.'], 502);
            }

            $result['max_pages'] = min(
                $result['last_page'],
                max(1, (int) config('services.sumselprov.max_pages', 5)),
            );

            return response()->json($result);
        }

        if ($request->input('mode') === 'resolve') {
            $validated = $request->validate([
                'action' => 'required|in:import,delete_reimport,overwrite,skip',
                'item' => 'required|array',
                'item.slug' => 'required|string|max:255',
                'item.judul' => 'required|string|max:500',
                'item.tgl' => 'nullable|date',
                'item.filegambar' => 'nullable|string|max:2048',
            ]);

            try {
                return response()->json($importer->resolveItem($validated['item'], $validated['action']));
            } catch (\Throwable $exception) {
                report($exception);

                return response()->json([
                    'message' => 'Berita "'.$validated['item']['judul'].'" gagal diproses.',
                ], 502);
            }
        }

        if ($request->filled('page')) {
            $validated = $request->validate(['page' => 'required|integer|min:1|max:33']);

            try {
                $result = $importer->importPage((int) $validated['page'], $endpoint);
            } catch (\Throwable $exception) {
                report($exception);

                return response()->json([
                    'message' => 'Sinkronisasi Sumselprov gagal. Silakan coba kembali.',
                ], 502);
            }

            $result['max_pages'] = min(
                $result['last_page'],
                max(1, (int) config('services.sumselprov.max_pages', 5)),
            );

            return response()->json($result);
        }

        try {
            $result = $importer->import(endpoint: $endpoint);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('message', 'Sinkronisasi Sumselprov gagal. Silakan coba kembali.');
        }

        return back()->with('success', sprintf(
            'Sinkronisasi selesai: %d baru, %d diperbarui, %d dilewati, %d gagal.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
            $result['failed'],
        ));
    }

    public function store(Request $request, SumselprovNewsImporter $imageService)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tanggal_rilis' => 'required|date',
            'penulis' => 'required|string|max:255',
            'media_publikasi' => 'nullable|string|max:255',
            'status' => 'required|in:draft,terpublikasi',
            'gambar_utama' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:20480',
            'gambar_pendukung' => 'nullable|array|max:10',
            'gambar_pendukung.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:20480',
            'sumber_url' => 'nullable|url:https|max:2048',
            'imported_image_urls' => 'nullable|array|max:11',
            'imported_image_urls.*' => 'url:https|max:2048',
        ]);

        if (! $request->hasFile('gambar_utama') && empty($validated['imported_image_urls'])) {
            throw ValidationException::withMessages([
                'gambar_utama' => 'Gambar utama wajib diunggah atau diambil dari tautan berita.',
            ]);
        }

        $validated['slug'] = Str::slug($validated['judul']);
        $remoteImages = $validated['imported_image_urls'] ?? [];
        unset($validated['gambar_utama'], $validated['gambar_pendukung'], $validated['imported_image_urls']);
        $storedImages = [];

        try {
            if ($request->hasFile('gambar_utama')) {
                $validated['gambar_utama'] = $imageService->storeUploadedImage(
                    $request->file('gambar_utama'),
                    $validated['slug'].'-utama-'.Str::uuid(),
                );
                $storedImages[] = $validated['gambar_utama'];
            } else {
                $validated['gambar_utama'] = $imageService->storeRemoteImage(
                    array_shift($remoteImages),
                    $validated['slug'].'-utama-'.Str::uuid(),
                );
                $storedImages[] = $validated['gambar_utama'];
            }

            $validated['gambar_pendukung'] = collect($request->file('gambar_pendukung', []))
                ->map(function ($file) use ($imageService, $validated, &$storedImages) {
                    $path = $imageService->storeUploadedImage($file, $validated['slug'].'-pendukung-'.Str::uuid());
                    $storedImages[] = $path;
                    return $path;
                })
                ->values()
                ->all();

            foreach (array_slice($remoteImages, 0, 10 - count($validated['gambar_pendukung'])) as $index => $url) {
                $path = $imageService->storeRemoteImage(
                    $url,
                    $validated['slug'].'-pendukung-'.$index.'-'.Str::uuid(),
                );
                $validated['gambar_pendukung'][] = $path;
                $storedImages[] = $path;
            }

            RilisBerita::create($validated);
        } catch (\Throwable $exception) {
            foreach ($storedImages as $path) {
                $imageService->deleteStoredImage($path);
            }
            throw $exception;
        }

        return redirect()->route('rilis-berita.index')->with('success', 'Rilis berita berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $rilisBerita = RilisBerita::findOrFail($id);
        $rilisBerita->setAttribute('gambar_url', $rilisBerita->gambar_utama
            ? URL::temporarySignedRoute('secure-files.rilis-image.signed', now()->addHour(), ['rilis' => $rilisBerita->id])
            : null);
        $rilisBerita->setAttribute('gambar_pendukung_urls', collect($rilisBerita->gambar_pendukung ?? [])
            ->values()
            ->map(fn ($path, $index) => URL::temporarySignedRoute(
                'secure-files.rilis-supporting-image.signed',
                now()->addHour(),
                ['rilis' => $rilisBerita->id, 'index' => $index],
            ))
            ->all());

        return Inertia::render('RilisBerita/Edit', [
            'rilisBerita' => $rilisBerita
        ]);
    }

    public function update(Request $request, $id, SumselprovNewsImporter $imageService)
    {
        $rilisBerita = RilisBerita::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tanggal_rilis' => 'required|date',
            'penulis' => 'required|string|max:255',
            'media_publikasi' => 'nullable|string|max:255',
            'status' => 'required|in:draft,terpublikasi',
            'gambar_utama' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:20480',
            'gambar_pendukung' => 'nullable|array|max:10',
            'gambar_pendukung.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:20480',
            'hapus_gambar_utama' => 'nullable|boolean',
            'hapus_gambar_pendukung' => 'nullable|array',
            'hapus_gambar_pendukung.*' => 'integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['judul']);
        unset($validated['gambar_utama'], $validated['gambar_pendukung'], $validated['hapus_gambar_utama'], $validated['hapus_gambar_pendukung']);

        $oldPrimary = $rilisBerita->gambar_utama;
        $oldSupporting = array_values($rilisBerita->gambar_pendukung ?? []);
        $removedIndexes = collect($request->input('hapus_gambar_pendukung', []))->map(fn ($index) => (int) $index)->unique();
        $remainingSupporting = collect($oldSupporting)->reject(fn ($path, $index) => $removedIndexes->contains($index))->values();
        $newSupportingFiles = collect($request->file('gambar_pendukung', []));

        if ($remainingSupporting->count() + $newSupportingFiles->count() > 10) {
            throw ValidationException::withMessages([
                'gambar_pendukung' => 'Jumlah gambar pendukung maksimal 10 gambar.',
            ]);
        }

        $storedImages = [];

        try {
            if ($request->hasFile('gambar_utama')) {
                $validated['gambar_utama'] = $imageService->storeUploadedImage(
                    $request->file('gambar_utama'),
                    $validated['slug'].'-utama-'.Str::uuid(),
                );
                $storedImages[] = $validated['gambar_utama'];
            } elseif ($request->boolean('hapus_gambar_utama')) {
                $validated['gambar_utama'] = null;
            }

            $newSupporting = $newSupportingFiles->map(function ($file) use ($imageService, $validated, &$storedImages) {
                $path = $imageService->storeUploadedImage($file, $validated['slug'].'-pendukung-'.Str::uuid());
                $storedImages[] = $path;
                return $path;
            });
            $validated['gambar_pendukung'] = $remainingSupporting->concat($newSupporting)->values()->all();

            $rilisBerita->update($validated);
        } catch (\Throwable $exception) {
            foreach ($storedImages as $path) {
                $imageService->deleteStoredImage($path);
            }
            throw $exception;
        }

        if (($request->hasFile('gambar_utama') || $request->boolean('hapus_gambar_utama')) && $oldPrimary) {
            $imageService->deleteStoredImage($oldPrimary);
        }
        $removedIndexes->each(function ($index) use ($oldSupporting, $imageService) {
            if (array_key_exists($index, $oldSupporting)) {
                $imageService->deleteStoredImage($oldSupporting[$index]);
            }
        });

        return redirect()->route('rilis-berita.index')->with('message', 'Rilis Berita berhasil diperbarui.');
    }

    public function destroy($id, SumselprovNewsImporter $imageService)
    {
        $rilisBerita = RilisBerita::findOrFail($id);

        if ($rilisBerita->gambar_utama && ! str_starts_with($rilisBerita->gambar_utama, 'http')) {
            $imageService->deleteStoredImage($rilisBerita->gambar_utama);
        }
        foreach ($rilisBerita->gambar_pendukung ?? [] as $path) {
            $imageService->deleteStoredImage($path);
        }

        $rilisBerita->delete();

        return redirect()->route('rilis-berita.index')->with('message', 'Rilis Berita berhasil dihapus.');
    }

    public function toggleStatus(RilisBerita $rilisBerita)
    {
        $nextStatus = match ($rilisBerita->status) {
            'draft' => 'terpublikasi',
            default => 'draft',
        };

        $rilisBerita->update(['status' => $nextStatus]);

        return back()->with('message', "Status rilis diubah menjadi {$nextStatus}.");
    }

}
