<?php

namespace App\Http\Controllers;

use App\Models\Dokumentasi;
use App\Models\DokumentasiMedia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class DokumentasiController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'jenis_media', 'status_verifikasi', 'tanggal_mulai', 'tanggal_selesai']);

        $dokumentasi = Dokumentasi::query()->with('mediaItems')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('pimpinan_terkait', 'like', "%{$search}%");
                });
            })
            ->when($filters['jenis_media'] ?? null, fn ($query, $type) => $query->whereHas('mediaItems', fn ($media) => $media->where('jenis_media', $type)))
            ->when($filters['status_verifikasi'] ?? null, fn ($query, $status) => $query->where('status_verifikasi', $status))
            ->when($filters['tanggal_mulai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['tanggal_selesai'] ?? null, fn ($query, $date) => $query->whereDate('tanggal', '<=', $date))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(9)
            ->withQueryString();

        $dokumentasi->getCollection()->each(fn (Dokumentasi $item) => $this->prepareForManagement($item));

        return Inertia::render('Dokumentasi/Index', [
            'dokumentasi' => $dokumentasi,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Dokumentasi/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'pimpinan_terkait' => 'nullable|string|max:255',
            'status_verifikasi' => 'required|in:draft,terverifikasi',
            'status_digitalisasi' => 'required|in:belum_didigitalisasi,sudah_didigitalisasi,sudah_diarsipkan',
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'required|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:51200',
            'thumbnails' => 'nullable|array',
            'thumbnails.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $this->ensureVideoThumbnails($request, 'files', 'thumbnails');
        $mediaRows = $this->storeMediaUploads($request->file('files'), $request->file('thumbnails', []));
        $storedPaths = collect($mediaRows)->flatMap(fn ($row) => [$row['file_path'], $row['thumbnail_path']])->filter();
        unset($validated['files'], $validated['thumbnails']);

        try {
            DB::transaction(function () use ($validated, $mediaRows) {
                $primary = $mediaRows[0];
                $dokumentasi = Dokumentasi::create([
                    ...$validated,
                    'jenis_media' => $primary['jenis_media'],
                    'file_path' => $primary['file_path'],
                    'thumbnail_path' => $primary['thumbnail_path'],
                ]);
                $dokumentasi->mediaItems()->createMany($mediaRows);
            });
        } catch (\Throwable $exception) {
            $storedPaths->each(fn ($path) => $this->deleteStoredFile($path));
            throw $exception;
        }

        return redirect()->route('dokumentasi.index')->with('success', 'Dokumentasi berhasil ditambahkan.');
    }

    public function storeFromInstagram(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'activity_date' => 'required|date_format:Y-m-d',
            'pimpinan_terkait' => 'nullable|string|max:255',
            'metadata_only' => 'sometimes|boolean',
            'photos' => 'nullable|array|min:1|max:20',
            'photos.*' => [
                'required',
                'file',
                'mimes:jpeg,jpg,mp4,mov,webm,mkv,avi,m4v',
                function ($attribute, $file, $fail) {
                    $isVideo = str_starts_with((string) $file->getMimeType(), 'video/');
                    if (! $isVideo && $file->getSize() > 50 * 1024 * 1024) {
                        $fail('Ukuran gambar maksimal 50 MB.');
                    }
                },
            ],
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $validated['activity_date']);
        $title = 'Kumpulan Kegiatan Tanggal '.$date->locale('id')->translatedFormat('d F Y');
        $incomingLeaders = collect(explode(';', $validated['pimpinan_terkait'] ?? ''))
            ->map(fn ($leader) => trim($leader))
            ->filter();
        $metadataOnly = $request->boolean('metadata_only');

        if ($metadataOnly) {
            $dokumentasi = Dokumentasi::where('judul', $title)
                ->whereDate('tanggal', $date->toDateString())
                ->first();
            if (! $dokumentasi) {
                return response()->json(['message' => 'Dokumentasi belum tersedia.'], 404);
            }
        } else {
            if (! $request->hasFile('photos')) {
                throw ValidationException::withMessages(['photos' => 'Minimal satu media wajib dikirim.']);
            }

            $dokumentasi = Dokumentasi::firstOrCreate(
                ['judul' => $title, 'tanggal' => $date->toDateString()],
                [
                    'jenis_media' => 'foto',
                    'file_path' => '',
                    'thumbnail_path' => null,
                    'pimpinan_terkait' => $incomingLeaders->join('; ') ?: null,
                    'status_verifikasi' => $incomingLeaders->isNotEmpty() ? 'terverifikasi' : 'draft',
                    'status_digitalisasi' => 'sudah_didigitalisasi',
                ]
            );
        }

        $leaders = collect(explode(';', (string) $dokumentasi->pimpinan_terkait))
            ->merge($incomingLeaders)
            ->map(fn ($leader) => trim($leader))
            ->filter()
            ->unique()
            ->sortBy(fn ($leader) =>
                [
                    'H. Herman Deru' => 1,
                    'H. Cik Ujang' => 2,
                    'Dr. H. Edward Candra' => 3,
                    'Hj. Febrita Lustia Herman Deru' => 4,
                ][$leader] ?? 999,
            )
            ->values();
        $dokumentasi->update([
            'pimpinan_terkait' => $leaders->join('; ') ?: null,
            'status_verifikasi' => $leaders->isNotEmpty() ? 'terverifikasi' : 'draft',
        ]);

        if ($metadataOnly) {
            return response()->json([
                'message' => 'Metadata dokumentasi berhasil diperbarui.',
                'dokumentasi_id' => $dokumentasi->id,
                'pimpinan_terkait' => $dokumentasi->pimpinan_terkait,
                'status_verifikasi' => $dokumentasi->status_verifikasi,
            ]);
        }

        $mediaRows = $this->storeMediaUploads(
            $request->file('photos'),
            [],
            ($dokumentasi->mediaItems()->max('urutan') ?? -1) + 1
        );
        $storedPaths = collect($mediaRows)->pluck('file_path');

        try {
            DB::transaction(function () use ($dokumentasi, $mediaRows) {
                $dokumentasi->mediaItems()->createMany($mediaRows);
                $primary = $dokumentasi->mediaItems()->firstOrFail();
                $dokumentasi->update([
                    'jenis_media' => $primary->jenis_media,
                    'file_path' => $primary->file_path,
                    'thumbnail_path' => $primary->thumbnail_path,
                ]);
            });
        } catch (\Throwable $exception) {
            $storedPaths->each(fn ($path) => $this->deleteStoredFile($path));
            throw $exception;
        }

        return response()->json([
            'message' => 'Dokumentasi Instagram berhasil diunggah ke SIGAP Sumsel.',
            'dokumentasi_id' => $dokumentasi->id,
            'judul' => $dokumentasi->judul,
            'media_count' => count($mediaRows),
        ]);
    }

    public function edit($id)
    {
        $dokumentasi = Dokumentasi::with('mediaItems')->findOrFail($id);
        $this->prepareForManagement($dokumentasi);
        return Inertia::render('Dokumentasi/Edit', [
            'dokumentasi' => $dokumentasi
        ]);
    }

    public function update(Request $request, $id)
    {
        $dokumentasi = Dokumentasi::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'pimpinan_terkait' => 'nullable|string|max:255',
            'status_verifikasi' => 'required|in:draft,terverifikasi',
            'status_digitalisasi' => 'required|in:belum_didigitalisasi,sudah_didigitalisasi,sudah_diarsipkan',
            'files' => 'nullable|array|max:20',
            'files.*' => 'required|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:51200',
            'thumbnails' => 'nullable|array',
            'thumbnails.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'removed_media' => 'nullable|array',
            'removed_media.*' => 'integer',
        ]);

        $this->ensureVideoThumbnails($request, 'files', 'thumbnails');
        $removed = $dokumentasi->mediaItems()->whereIn('id', $validated['removed_media'] ?? [])->get();
        $files = $request->file('files', []);
        if (($dokumentasi->mediaItems()->count() - $removed->count() + count($files)) < 1) {
            throw ValidationException::withMessages(['files' => 'Kegiatan harus memiliki minimal satu foto atau video.']);
        }

        $mediaRows = $this->storeMediaUploads($files, $request->file('thumbnails', []), $dokumentasi->mediaItems()->max('urutan') + 1);
        $newPaths = collect($mediaRows)->flatMap(fn ($row) => [$row['file_path'], $row['thumbnail_path']])->filter();
        $removedPaths = $removed->flatMap(fn ($media) => [$media->file_path, $media->thumbnail_path])->filter()->unique();
        unset($validated['files'], $validated['thumbnails'], $validated['removed_media']);

        try {
            DB::transaction(function () use ($dokumentasi, $validated, $removed, $mediaRows) {
                $dokumentasi->mediaItems()->whereIn('id', $removed->pluck('id'))->delete();
                $dokumentasi->mediaItems()->createMany($mediaRows);
                $primary = $dokumentasi->mediaItems()->firstOrFail();
                $dokumentasi->update([
                    ...$validated,
                    'jenis_media' => $primary->jenis_media,
                    'file_path' => $primary->file_path,
                    'thumbnail_path' => $primary->thumbnail_path,
                ]);
            });
        } catch (\Throwable $exception) {
            $newPaths->each(fn ($path) => $this->deleteStoredFile($path));
            throw $exception;
        }
        $removedPaths->each(fn ($path) => $this->deleteStoredFile($path));

        return redirect()->route('dokumentasi.index')->with('message', 'Dokumentasi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $dokumentasi = Dokumentasi::with('mediaItems')->findOrFail($id);
        $paths = $dokumentasi->mediaItems
            ->flatMap(fn ($media) => [$media->file_path, $media->thumbnail_path])
            ->merge([$dokumentasi->file_path, $dokumentasi->thumbnail_path])
            ->filter()->unique();
        $dokumentasi->delete();
        $paths->each(fn ($path) => $this->deleteStoredFile($path));

        return redirect()->route('dokumentasi.index')->with('message', 'Dokumentasi berhasil dihapus.');
    }

    public function toggleStatus(Dokumentasi $dokumentasi)
    {
        $nextStatus = $dokumentasi->status_verifikasi === 'draft' ? 'terverifikasi' : 'draft';

        $dokumentasi->update(['status_verifikasi' => $nextStatus]);

        return back()->with('message', "Status dokumentasi diubah menjadi {$nextStatus}.");
    }

    private function prepareForManagement(Dokumentasi $dokumentasi): void
    {
        $dokumentasi->mediaItems->each(function (DokumentasiMedia $media) {
            $media->setAttribute('file_url', route('secure-files.dokumentasi-media', $media));
            $media->setAttribute('thumbnail_url', $media->thumbnail_path
                ? URL::temporarySignedRoute('secure-files.dokumentasi-media-thumbnail.signed', now()->addHour(), ['media' => $media->id])
                : null);
        });
        $primary = $dokumentasi->mediaItems->first();
        $dokumentasi->setAttribute('media_count', $dokumentasi->mediaItems->count());
        $dokumentasi->setAttribute('file_url', $primary?->getAttribute('file_url'));
        $dokumentasi->setAttribute('thumbnail_url', $primary?->getAttribute('thumbnail_url'));
        $dokumentasi->setAttribute('jenis_media', $dokumentasi->mediaItems->pluck('jenis_media')->unique()->count() > 1 ? 'campuran' : ($primary?->jenis_media ?? $dokumentasi->jenis_media));
    }

    private function ensureVideoThumbnails(Request $request, string $filesKey, string $thumbnailsKey): void
    {
        foreach ($request->file($filesKey, []) as $index => $file) {
            if (str_starts_with((string) $file->getMimeType(), 'video/') && ! $request->hasFile("{$thumbnailsKey}.{$index}")) {
                throw ValidationException::withMessages(["{$thumbnailsKey}.{$index}" => 'Thumbnail video wajib tersedia.']);
            }
        }
    }

    private function storeMediaUploads(array $files, array $thumbnails, int $startOrder = 0): array
    {
        $rows = [];
        $storedPaths = [];
        try {
            foreach ($files as $index => $file) {
                $isVideo = str_starts_with((string) $file->getMimeType(), 'video/');
                $filePath = $this->storeUploadedFile($file);
                $storedPaths[] = $filePath;
                $thumbnailPath = isset($thumbnails[$index]) ? $this->storeUploadedFile($thumbnails[$index], 'uploads/dokumentasi/thumbnails') : null;
                if ($thumbnailPath) {
                    $storedPaths[] = $thumbnailPath;
                }
                $rows[] = [
                    'jenis_media' => $isVideo ? 'video' : 'foto',
                    'file_path' => $filePath,
                    'thumbnail_path' => $thumbnailPath,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'urutan' => $startOrder + $index,
                ];
            }
        } catch (\Throwable $exception) {
            collect($storedPaths)->each(fn ($path) => $this->deleteStoredFile($path));
            throw $exception;
        }

        return $rows;
    }

    private function deleteStoredFile(string $path): void
    {
        $path = str_starts_with($path, '/storage/')
            ? ltrim(str_replace('/storage/', '', $path), '/')
            : (str_starts_with($path, 'public/') ? substr($path, 7) : $path);

        foreach ($this->storageDisks() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                return;
            }
        }
    }

    private function storeUploadedFile(\Illuminate\Http\UploadedFile $file, string $directory = 'uploads/dokumentasi'): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw new \RuntimeException('File upload tidak dapat dibaca.');
        }

        try {
            Storage::disk($this->storageDisk())->put($path, $stream);
        } finally {
            fclose($stream);
        }

        return $path;
    }

    private function storageDisk(): string
    {
        $disk = (string) config('services.dokumentasi.storage_disk', 'local');

        if (! in_array($disk, ['local', 'google-drive'], true)) {
            throw new \RuntimeException('DOKUMENTASI_STORAGE_DISK harus local atau google-drive.');
        }

        return $disk;
    }

    private function storageDisks(): array
    {
        return array_values(array_unique([$this->storageDisk(), 'local', 'google-drive', 'public']));
    }
}
