<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dokumentasi;
use App\Models\Kliping;
use App\Models\RilisBerita;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Services\RemoteFileCache;

class AppBhpContentController extends Controller
{
    public function __construct(private RemoteFileCache $fileCache)
    {
    }
    public function rilis(Request $request): JsonResponse
    {
        $query = RilisBerita::query()
            ->where('status', 'terpublikasi')
            ->when($request->string('search')->toString(), function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('isi', 'like', "%{$search}%");
                });
            })
            ->latest('tanggal_rilis');

        $page = $query->paginate($this->perPage($request))->withQueryString();
        $page->through(fn (RilisBerita $item) => $this->serializeRilis($item));

        return response()->json($page);
    }

    public function rilisDetail(string $slug): JsonResponse
    {
        $rilis = RilisBerita::where('slug', $slug)
            ->where('status', 'terpublikasi')
            ->firstOrFail();

        return response()->json(['data' => $this->serializeRilis($rilis)]);
    }

    public function kliping(Request $request): JsonResponse
    {
        $query = Kliping::query()
            ->where('status', 'terpublikasi')
            ->when($request->string('search')->toString(), function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('media', 'like', "%{$search}%");
                });
            })
            ->latest('tanggal');

        $page = $query->paginate($this->perPage($request))->withQueryString();
        $page->through(fn (Kliping $item) => $this->serializeKliping($item));

        return response()->json($page);
    }

    public function klipingDetail(Kliping $kliping): JsonResponse
    {
        abort_unless($kliping->status === 'terpublikasi', 404);

        return response()->json(['data' => $this->serializeKliping($kliping)]);
    }

    public function dokumentasi(Request $request): JsonResponse
    {
        $query = Dokumentasi::query()->with('mediaItems')
            ->where('status_verifikasi', 'terverifikasi')
            ->when($request->string('search')->toString(), function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('pimpinan_terkait', 'like', "%{$search}%");
                });
            })
            ->latest('tanggal');

        $page = $query->paginate($this->perPage($request))->withQueryString();
        $page->through(fn (Dokumentasi $item) => $this->serializeDokumentasi($item));

        return response()->json($page);
    }

    public function dokumentasiDetail(Dokumentasi $dokumentasi): JsonResponse
    {
        abort_unless($dokumentasi->status_verifikasi === 'terverifikasi', 404);
        $dokumentasi->load('mediaItems');

        return response()->json(['data' => $this->serializeDokumentasi($dokumentasi)]);
    }

    private function serializeKliping(Kliping $kliping): array
    {
        $data = $kliping->makeHidden(['file_path', 'file_url'])->toArray();
        $data['file_url'] = $kliping->file_path
            ? URL::temporarySignedRoute('secure-files.kliping.signed', $this->signedUrlExpiration(), ['kliping' => $kliping->id])
            : null;

        return $data;
    }

    private function serializeRilis(RilisBerita $rilis): array
    {
        $data = $rilis->makeHidden(['gambar_utama', 'gambar_pendukung'])->toArray();
        $data['gambar_url'] = $rilis->gambar_utama
            ? ($this->fileCache->publicUrl($rilis->gambar_utama)
                ?? URL::temporarySignedRoute('secure-files.rilis-image.signed', $this->signedUrlExpiration(), ['rilis' => $rilis->id]))
            : null;
        $data['gambar_pendukung_urls'] = collect($rilis->gambar_pendukung ?? [])
            ->values()
            ->map(fn ($path, $index) => $this->fileCache->publicUrl($path)
                ?? URL::temporarySignedRoute(
                    'secure-files.rilis-supporting-image.signed',
                    $this->signedUrlExpiration(),
                    ['rilis' => $rilis->id, 'index' => $index],
                ))
            ->all();

        return $data;
    }

    private function serializeDokumentasi(Dokumentasi $dokumentasi): array
    {
        $data = $dokumentasi->makeHidden(['file_path', 'file_url', 'thumbnail_path'])->toArray();
        unset($data['media_items']);
        $media = $dokumentasi->mediaItems->map(fn ($item) => [
            'id' => $item->id,
            'jenis_media' => $item->jenis_media,
            'original_name' => $item->original_name,
            'size' => $item->size,
            'urutan' => $item->urutan,
            'file_url' => URL::temporarySignedRoute('secure-files.dokumentasi-media.signed', $this->signedUrlExpiration(), ['media' => $item->id]),
            'thumbnail_url' => $item->thumbnail_path
                ? URL::temporarySignedRoute('secure-files.dokumentasi-media-thumbnail.signed', $this->signedUrlExpiration(), ['media' => $item->id])
                : null,
        ])->values();
        if ($media->isEmpty() && $dokumentasi->file_path) {
            $media->push([
                'id' => null,
                'jenis_media' => $dokumentasi->jenis_media,
                'original_name' => null,
                'size' => null,
                'urutan' => 0,
                'file_url' => URL::temporarySignedRoute('secure-files.dokumentasi.signed', $this->signedUrlExpiration(), ['dokumentasi' => $dokumentasi->id]),
                'thumbnail_url' => $dokumentasi->thumbnail_path
                    ? URL::temporarySignedRoute('secure-files.dokumentasi-thumbnail.signed', $this->signedUrlExpiration(), ['dokumentasi' => $dokumentasi->id])
                    : null,
            ]);
        }
        $primary = $media->first();
        $types = $media->pluck('jenis_media')->unique();
        $data['media'] = $media->all();
        $data['media_count'] = $media->count();
        $data['jenis_media'] = $types->count() > 1 ? 'campuran' : ($primary['jenis_media'] ?? $dokumentasi->jenis_media);
        $data['file_url'] = $primary['file_url'] ?? null;
        $data['thumbnail_url'] = $primary['thumbnail_url'] ?? null;
        $data['cover_url'] = $primary['thumbnail_url'] ?? (($primary['jenis_media'] ?? null) === 'foto' ? ($primary['file_url'] ?? null) : null);

        return $data;
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 12), 1), 50);
    }

    private function signedUrlExpiration(): \DateTimeInterface
    {
        return now()->addDay()->endOfDay();
    }
}
