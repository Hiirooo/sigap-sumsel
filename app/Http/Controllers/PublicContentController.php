<?php

namespace App\Http\Controllers;

use App\Models\Dokumentasi;
use App\Models\Kliping;
use App\Models\RilisBerita;
use App\Services\RemoteFileCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicContentController extends Controller
{
    public function __construct(private RemoteFileCache $fileCache)
    {
    }

    public function rilisIndex(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $items = RilisBerita::query()
            ->where('status', 'terpublikasi')
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('judul', 'like', "%{$search}%")
                    ->orWhere('isi', 'like', "%{$search}%");
            }))
            ->latest('tanggal_rilis')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (RilisBerita $item) => $this->serializeRilis($item, false));

        return $this->renderIndex('rilis', 'Rilis Berita', 'Publikasi resmi dan informasi terkini Pemerintah Provinsi Sumatera Selatan.', $items, $search);
    }

    public function rilisShow(string $slug): Response
    {
        $item = RilisBerita::query()
            ->where('slug', $slug)
            ->where('status', 'terpublikasi')
            ->firstOrFail();

        return $this->renderShow(
            'rilis',
            $this->serializeRilis($item),
            RilisBerita::query()
                ->where('status', 'terpublikasi')
                ->whereKeyNot($item->id)
                ->latest('tanggal_rilis')
                ->take(3)
                ->get()
                ->map(fn (RilisBerita $related) => $this->serializeRilis($related, false)),
        );
    }

    public function galeriIndex(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $items = Dokumentasi::query()
            ->with('mediaItems')
            ->where('status_verifikasi', 'terverifikasi')
            ->where('is_archived', false)
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('judul', 'like', "%{$search}%")
                    ->orWhere('narasi', 'like', "%{$search}%")
                    ->orWhere('pimpinan_terkait', 'like', "%{$search}%");
            }))
            ->latest('tanggal')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Dokumentasi $item) => $this->serializeDokumentasi($item, false));

        return $this->renderIndex('galeri', 'Galeri Dokumentasi', 'Rekam visual kegiatan dan agenda resmi Pemerintah Provinsi Sumatera Selatan.', $items, $search);
    }

    public function galeriShow(Dokumentasi $dokumentasi): Response
    {
        abort_unless($dokumentasi->status_verifikasi === 'terverifikasi' && ! $dokumentasi->is_archived, 404);
        $dokumentasi->load('mediaItems');

        return $this->renderShow(
            'galeri',
            $this->serializeDokumentasi($dokumentasi),
            Dokumentasi::query()
                ->with('mediaItems')
                ->where('status_verifikasi', 'terverifikasi')
                ->where('is_archived', false)
                ->whereKeyNot($dokumentasi->id)
                ->latest('tanggal')
                ->take(3)
                ->get()
                ->map(fn (Dokumentasi $related) => $this->serializeDokumentasi($related, false)),
        );
    }

    public function klipingIndex(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $items = Kliping::query()
            ->where('status', 'terpublikasi')
            ->where('is_archived', false)
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('judul', 'like', "%{$search}%")
                    ->orWhere('media', 'like', "%{$search}%");
            }))
            ->latest('tanggal')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Kliping $item) => $this->serializeKliping($item, false));

        return $this->renderIndex('kliping', 'Kliping Media', 'Pantauan pemberitaan media tentang Pemerintah Provinsi Sumatera Selatan.', $items, $search);
    }

    public function klipingShow(Kliping $kliping): Response
    {
        abort_unless($kliping->status === 'terpublikasi' && ! $kliping->is_archived, 404);

        return $this->renderShow(
            'kliping',
            $this->serializeKliping($kliping),
            Kliping::query()
                ->where('status', 'terpublikasi')
                ->where('is_archived', false)
                ->whereKeyNot($kliping->id)
                ->latest('tanggal')
                ->take(3)
                ->get()
                ->map(fn (Kliping $related) => $this->serializeKliping($related, false)),
        );
    }

    private function renderIndex(string $type, string $title, string $description, $items, string $search): Response
    {
        return Inertia::render('Public/ContentIndex', compact('type', 'title', 'description', 'items', 'search'));
    }

    private function renderShow(string $type, array $item, $related): Response
    {
        return Inertia::render('Public/ContentShow', compact('type', 'item', 'related'));
    }

    private function serializeRilis(RilisBerita $rilis, bool $withContent = true): array
    {
        $content = $this->plainText($rilis->isi);

        return [
            'id' => $rilis->id,
            'title' => $rilis->judul,
            'date' => $rilis->tanggal_rilis,
            'author' => $rilis->penulis,
            'media_publication' => $rilis->media_publikasi,
            'excerpt' => Str::limit($content, 180),
            'content' => $withContent ? $content : null,
            'image_url' => $rilis->gambar_utama
                ? ($this->fileCache->publicUrl($rilis->gambar_utama)
                    ?? URL::temporarySignedRoute('secure-files.rilis-image.signed', $this->signedUrlExpiration(), ['rilis' => $rilis->id]))
                : null,
            'gallery' => $withContent ? collect($rilis->gambar_pendukung ?? [])->values()->map(
                fn ($path, $index) => $this->fileCache->publicUrl($path)
                    ?? URL::temporarySignedRoute('secure-files.rilis-supporting-image.signed', $this->signedUrlExpiration(), ['rilis' => $rilis->id, 'index' => $index]),
            )->all() : [],
            'href' => route('public.rilis.show', $rilis->slug),
        ];
    }

    private function serializeDokumentasi(Dokumentasi $dokumentasi, bool $withMedia = true): array
    {
        $media = $dokumentasi->mediaItems->map(fn ($item) => [
            'id' => $item->id,
            'type' => $item->jenis_media,
            'name' => $item->original_name,
            'file_url' => URL::temporarySignedRoute('secure-files.dokumentasi-media.signed', $this->signedUrlExpiration(), ['media' => $item->id]),
            'thumbnail_url' => $item->thumbnail_path
                ? URL::temporarySignedRoute('secure-files.dokumentasi-media-thumbnail.signed', $this->signedUrlExpiration(), ['media' => $item->id])
                : null,
        ])->values();

        if ($media->isEmpty() && $dokumentasi->file_path) {
            $media->push([
                'id' => null,
                'type' => $dokumentasi->jenis_media,
                'name' => null,
                'file_url' => URL::temporarySignedRoute('secure-files.dokumentasi.signed', $this->signedUrlExpiration(), ['dokumentasi' => $dokumentasi->id]),
                'thumbnail_url' => $dokumentasi->thumbnail_path
                    ? URL::temporarySignedRoute('secure-files.dokumentasi-thumbnail.signed', $this->signedUrlExpiration(), ['dokumentasi' => $dokumentasi->id])
                    : null,
            ]);
        }

        $primary = $media->first();
        $types = $media->pluck('type')->unique();
        $type = $types->count() > 1 ? 'campuran' : ($primary['type'] ?? $dokumentasi->jenis_media);

        return [
            'id' => $dokumentasi->id,
            'title' => $dokumentasi->judul,
            'date' => $dokumentasi->tanggal,
            'leader' => $dokumentasi->pimpinan_terkait,
            'excerpt' => Str::limit($this->plainText($dokumentasi->narasi), 180),
            'content' => $withMedia ? $this->plainText($dokumentasi->narasi) : null,
            'media_type' => $type,
            'media_count' => $media->count(),
            'image_url' => $primary['thumbnail_url'] ?? (($primary['type'] ?? null) === 'foto' ? ($primary['file_url'] ?? null) : null),
            'media' => $withMedia ? $media->all() : [],
            'href' => route('public.galeri.show', $dokumentasi),
        ];
    }

    private function serializeKliping(Kliping $kliping, bool $withContent = true): array
    {
        $content = $this->plainText($kliping->isi_berita);

        return [
            'id' => $kliping->id,
            'title' => $kliping->judul,
            'date' => $kliping->tanggal,
            'source' => $kliping->media,
            'sentiment' => $kliping->sentimen,
            'excerpt' => Str::limit($content, 180),
            'content' => $withContent ? $content : null,
            'file_url' => $withContent && $kliping->file_path
                ? URL::temporarySignedRoute('secure-files.kliping.signed', $this->signedUrlExpiration(), ['kliping' => $kliping->id])
                : null,
            'external_url' => $withContent && filter_var($kliping->url, FILTER_VALIDATE_URL) ? $kliping->url : null,
            'href' => route('public.kliping.show', $kliping),
        ];
    }

    private function plainText(?string $value): string
    {
        return Str::of(html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'))->squish()->toString();
    }

    private function signedUrlExpiration(): \DateTimeInterface
    {
        return now()->addDay()->endOfDay();
    }
}
