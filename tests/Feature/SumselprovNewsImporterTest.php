<?php

namespace Tests\Feature;

use App\Models\RilisBerita;
use App\Services\SumselprovNewsImporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SumselprovNewsImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_each_sumselprov_release_only_once(): void
    {
        config([
            'filesystems.default' => 'local',
            'services.rilis.image_convert_webp' => true,
            'services.rilis.image_storage_disk' => 'local',
        ]);
        Storage::fake('local');
        Http::fake([
            '*/api_berita_all2*' => Http::response(['data' => [[
                'judul' => 'Berita Sumsel',
                'slug' => 'berita-sumsel',
                'tgl' => '2026-07-14 08:00:00',
                'filegambar' => 'public/berita/gambar.jpg',
            ]]]),
            '*/beritadetailslug*' => Http::response([
                'isi' => '<p>Isi lengkap berita.</p>',
                'gambar' => 'public/berita/detail.jpg,public/berita/lain.jpg',
            ]),
            'https://sumselprov.go.id/storage/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']),
        ]);

        $importer = app(SumselprovNewsImporter::class);

        $first = $importer->import(1, 'api_berita_all2');
        $second = $importer->import(1, 'api_berita_all2');

        $this->assertSame(1, $first['created']);
        $this->assertSame(1, $second['skipped']);
        $this->assertDatabaseCount('rilis_beritas', 1);
        $this->assertDatabaseHas('rilis_beritas', [
            'slug' => 'berita-sumsel',
            'status' => 'terpublikasi',
            'is_archived' => true,
            'media_publikasi' => 'sumselprov.go.id',
            'gambar_utama' => 'uploads/rilis/sumselprov/berita-sumsel.webp',
        ]);
        Storage::disk('local')->assertExists('uploads/rilis/sumselprov/berita-sumsel.webp');
        $imageInfo = getimagesizefromstring(Storage::disk('local')->get('uploads/rilis/sumselprov/berita-sumsel.webp'));
        $this->assertSame('image/webp', $imageInfo['mime']);
        $this->assertSame([], Storage::disk('local')->allFiles('temp/sumselprov'));

        $detailRequests = Http::recorded(
            fn ($request) => str_contains($request->url(), 'beritadetailslug')
        );
        $this->assertCount(1, $detailRequests);
    }

    public function test_it_can_keep_the_original_format_on_local_storage(): void
    {
        config([
            'services.rilis.image_convert_webp' => false,
            'services.rilis.image_storage_disk' => 'local',
        ]);
        Storage::fake('local');
        Http::fake([
            '*/api_berita_all2*' => Http::response(['data' => [[
                'judul' => 'Berita Format Asli',
                'slug' => 'berita-format-asli',
                'tgl' => '2026-07-14 08:00:00',
                'filegambar' => 'public/berita/gambar.png',
            ]]]),
            '*/beritadetailslug*' => Http::response([
                'isi' => '<p>Isi berita.</p>',
                'gambar' => 'public/berita/gambar.png',
            ]),
            'https://sumselprov.go.id/storage/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']),
        ]);

        app(SumselprovNewsImporter::class)->import(1, 'api_berita_all2');

        $path = 'uploads/rilis/sumselprov/berita-format-asli.png';
        $this->assertDatabaseHas('rilis_beritas', ['gambar_utama' => $path]);
        Storage::disk('local')->assertExists($path);
        $this->assertSame('image/png', getimagesizefromstring(Storage::disk('local')->get($path))['mime']);
        $this->assertSame([], Storage::disk('local')->allFiles('temp/sumselprov'));
    }

    public function test_manual_sync_endpoint_reports_page_progress(): void
    {
        config(['services.sumselprov.max_pages' => 5]);
        Http::fake([
            '*/api_berita_all2*' => Http::response([
                'data' => [],
                'current_page' => 2,
                'last_page' => 8,
            ]),
        ]);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->postJson(route('rilis-berita.sync-sumselprov'), ['page' => 2, 'endpoint' => 'api_berita_all2'])
            ->assertOk()
            ->assertJson([
                'current_page' => 2,
                'last_page' => 8,
                'max_pages' => 5,
                'item_count' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
            ]);
    }

    public function test_it_falls_back_to_next_sumselprov_endpoint_when_first_fails(): void
    {
        config(['services.sumselprov.api_endpoints' => ['api_berita_all2', 'api_berita_sumsel3']]);
        Http::fake([
            '*/api_berita_all2*' => Http::response([], 404),
            '*/api_berita_sumsel3*' => Http::response([[
                'judul' => 'Berita API Baru',
                'slug' => 'berita-api-baru',
                'tgl' => 'Tuesday, 28 July 2026',
                'filegambar' => 'https://sumselprov.go.id/storage/berita/gambar-baru.jpg',
            ]]),
        ]);

        $result = app(SumselprovNewsImporter::class)->previewPage(1);

        $this->assertCount(1, $result['items']);
        $this->assertSame('berita-api-baru', $result['items'][0]['slug']);
    }

    public function test_it_combines_old_and_new_sumselprov_api_formats_without_duplicate_slugs(): void
    {
        config(['services.sumselprov.api_endpoints' => ['api_berita_sumsel3', 'api_berita_all2']]);
        Http::fake([
            '*/api_berita_sumsel3*' => Http::response([[
                'judul' => 'Berita API Baru',
                'slug' => 'berita-api-baru',
                'tgl' => 'Tuesday, 28 July 2026',
                'filegambar' => 'https://sumselprov.go.id/storage/berita/gambar-baru.jpg',
            ], [
                'judul' => 'Berita Sama',
                'slug' => 'berita-sama',
                'tgl' => 'Tuesday, 28 July 2026',
                'filegambar' => 'https://sumselprov.go.id/storage/berita/gambar-sama.jpg',
            ]]),
            '*/api_berita_all2*' => Http::response([
                'data' => [[
                    'judul' => 'Berita API Lama',
                    'slug' => 'berita-api-lama',
                    'tgl' => '2026-07-28 08:00:00',
                    'filegambar' => 'public/berita/gambar-lama.jpg',
                ], [
                    'judul' => 'Berita Sama Lama',
                    'slug' => 'berita-sama',
                    'tgl' => '2026-07-28 09:00:00',
                    'filegambar' => 'public/berita/gambar-sama-lama.jpg',
                ]],
                'current_page' => 1,
                'last_page' => 4,
            ]),
        ]);

        $result = app(SumselprovNewsImporter::class)->previewPage(1);

        $this->assertSame(4, $result['last_page']);
        $this->assertSame([
            'berita-api-baru',
            'berita-sama',
            'berita-api-lama',
        ], array_column($result['items'], 'slug'));
    }

    public function test_it_imports_new_sumselprov_api_item_even_when_detail_endpoint_is_missing(): void
    {
        config([
            'services.sumselprov.api_endpoints' => ['api_berita_sumsel3'],
            'services.rilis.image_convert_webp' => true,
            'services.rilis.image_storage_disk' => 'local',
        ]);
        Storage::fake('local');
        Http::fake([
            '*/api_berita_sumsel3*' => Http::response([[
                'judul' => 'Berita API Baru',
                'slug' => 'berita-api-baru',
                'tgl' => 'Tuesday, 28 July 2026',
                'filegambar' => 'https://sumselprov.go.id/storage/berita/gambar-baru.jpg',
            ]]),
            '*/beritadetailslug*' => Http::response(['message' => 'Not Found'], 404),
            'https://sumselprov.go.id/storage/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']),
        ]);

        $result = app(SumselprovNewsImporter::class)->import(1);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('rilis_beritas', [
            'judul' => 'Berita API Baru',
            'slug' => 'berita-api-baru',
            'tanggal_rilis' => '2026-07-28',
            'isi' => '',
            'gambar_utama' => 'uploads/rilis/sumselprov/berita-api-baru.webp',
        ]);
        Storage::disk('local')->assertExists('uploads/rilis/sumselprov/berita-api-baru.webp');
    }

    public function test_duplicate_actions_overwrite_or_delete_and_reimport_as_selected(): void
    {
        config([
            'services.rilis.image_convert_webp' => true,
            'services.rilis.image_storage_disk' => 'local',
        ]);
        Storage::fake('local');
        $oldPrimary = 'uploads/rilis/old-primary.webp';
        $oldSupporting = 'uploads/rilis/old-supporting.webp';
        Storage::disk('local')->put($oldPrimary, 'old-primary');
        Storage::disk('local')->put($oldSupporting, 'old-supporting');
        $rilis = RilisBerita::create([
            'judul' => 'Judul Lama',
            'slug' => 'berita-sama',
            'isi' => 'Isi lama',
            'tanggal_rilis' => '2026-07-01',
            'penulis' => 'Editor',
            'status' => 'terpublikasi',
            'gambar_utama' => $oldPrimary,
            'gambar_pendukung' => [$oldSupporting],
        ]);
        Http::fake([
            '*/beritadetailslug*' => Http::response([
                'judul' => 'Judul Baru',
                'isi' => '<p>Isi terbaru.</p>',
                'gambar' => 'public/berita/new.png',
            ]),
            'https://sumselprov.go.id/storage/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']),
        ]);
        $operator = User::factory()->create(['role' => 'operator']);
        $item = [
            'slug' => 'berita-sama',
            'judul' => 'Judul Baru',
            'tgl' => '2026-07-14 08:00:00',
            'filegambar' => 'public/berita/new.png',
        ];

        $this->actingAs($operator)->postJson(route('rilis-berita.sync-sumselprov'), [
            'mode' => 'resolve',
            'action' => 'overwrite',
            'item' => $item,
        ])->assertOk()->assertJsonPath('updated', 1);

        $overwritten = RilisBerita::where('slug', 'berita-sama')->firstOrFail();
        $this->assertSame($rilis->id, $overwritten->id);
        $this->assertSame([$oldSupporting], $overwritten->gambar_pendukung);
        $this->assertSame('<p>Isi terbaru.</p>', $overwritten->isi);
        Storage::disk('local')->assertMissing($oldPrimary);

        $this->actingAs($operator)->postJson(route('rilis-berita.sync-sumselprov'), [
            'mode' => 'resolve',
            'action' => 'delete_reimport',
            'item' => $item,
        ])->assertOk()->assertJsonPath('created', 1);

        $reimported = RilisBerita::where('slug', 'berita-sama')->firstOrFail();
        $this->assertNotSame($rilis->id, $reimported->id);
        $this->assertSame([], $reimported->gambar_pendukung ?? []);
        Storage::disk('local')->assertMissing($oldSupporting);
    }
}
