<?php

namespace Tests\Feature;

use App\Models\Kliping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KlipingStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_toggle_kliping_publication_and_archive_status(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $kliping = Kliping::create([
            'judul' => 'Kliping Status',
            'media' => 'Media Sumsel',
            'tanggal' => '2026-07-14',
            'sentimen' => 'netral',
            'status' => 'draft',
        ]);

        foreach (['terpublikasi', 'draft'] as $expectedStatus) {
            $this->actingAs($operator)
                ->patch(route('kliping.toggle-status', $kliping))
                ->assertRedirect();

            $this->assertSame($expectedStatus, $kliping->refresh()->status);
        }

        $this->actingAs($operator)
            ->post(route('kliping.toggle-archive', $kliping))
            ->assertRedirect();
        $this->assertTrue($kliping->refresh()->is_archived);

        $this->actingAs($operator)
            ->post(route('kliping.toggle-archive', $kliping))
            ->assertRedirect();
        $this->assertFalse($kliping->refresh()->is_archived);
    }

    public function test_kliping_index_supports_preset_custom_and_all_page_sizes(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        foreach (range(1, 20) as $index) {
            Kliping::create([
                'judul' => "Kliping {$index}",
                'media' => 'Media Sumsel',
                'tanggal' => '2026-07-20',
                'sentimen' => 'netral',
                'status' => 'draft',
            ]);
        }
        Kliping::create([
            'judul' => 'Kliping terpublikasi',
            'media' => 'Media Sumsel',
            'tanggal' => '2026-07-20',
            'sentimen' => 'netral',
            'status' => 'terpublikasi',
        ]);
        Kliping::create([
            'judul' => 'Kliping arsip',
            'media' => 'Media Sumsel',
            'tanggal' => '2026-07-20',
            'sentimen' => 'netral',
            'status' => 'terpublikasi',
            'is_archived' => true,
        ]);

        $this->actingAs($operator)
            ->get(route('kliping.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kliping/Index')
                ->has('kliping.data', 10)
                ->where('kliping.total', 20)
                ->where('kliping.last_page', 2)
                ->where('kliping.current_page', 1)
                ->where('filters.status', 'draft')
                ->where('filters.arsip', 'belum')
                ->where('statusCounts.draft', 20)
                ->where('statusCounts.terpublikasi', 1));

        $this->actingAs($operator)
            ->get(route('kliping.index', ['per_page' => 7]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('kliping.data', 7)
                ->where('kliping.per_page', 7)
                ->where('filters.per_page', '7'));

        $this->actingAs($operator)
            ->get(route('kliping.index', ['per_page' => 'all']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('kliping.data', 20)
                ->where('kliping.total', 20)
                ->where('kliping.last_page', 1)
                ->where('filters.per_page', 'all'));

        $this->actingAs($operator)
            ->get(route('kliping.index', ['status' => 'terpublikasi']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('kliping.data', 1)
                ->where('kliping.total', 1)
                ->where('filters.status', 'terpublikasi'));

        $this->actingAs($operator)
            ->get(route('kliping.index', ['status' => 'terpublikasi', 'arsip' => 'sudah']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('kliping.data', 1)
                ->where('kliping.data.0.judul', 'Kliping arsip')
                ->where('filters.arsip', 'sudah'));
    }

    public function test_missing_kliping_file_redirects_to_original_article(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('google-drive');
        $operator = User::factory()->create(['role' => 'operator']);
        $url = 'https://media.example.com/berita/kliping-sumsel';
        Http::fake([$url => Http::response([], 404)]);
        $kliping = Kliping::create([
            'judul' => 'Kliping Tanpa File Fisik',
            'media' => 'Media Sumsel',
            'tanggal' => '2026-07-20',
            'sentimen' => 'netral',
            'status' => 'draft',
            'file_path' => 'uploads/kliping/file-hilang.jpg',
            'url' => $url,
        ]);

        $this->actingAs($operator)
            ->get(route('secure-files.kliping', $kliping))
            ->assertRedirect($url);
    }

    public function test_missing_kliping_image_is_recovered_from_original_article(): void
    {
        config(['services.kliping.storage_disk' => 'local']);
        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('google-drive');
        $operator = User::factory()->create(['role' => 'operator']);
        $url = 'https://media.example.com/berita/gambar-kliping';
        $imageUrl = 'https://media.example.com/images/gambar-kliping.jpg';
        $image = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAEf/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k=');
        Http::fake([
            $url => Http::response(<<<HTML
<!doctype html><html><head>
<meta property="og:title" content="Berita Gambar Kliping">
<meta property="og:image" content="{$imageUrl}">
</head><body><article><p>Isi artikel berita ini cukup panjang untuk dapat dikenali sebagai isi artikel utama oleh sistem ekstraksi kliping media Sumatera Selatan.</p></article></body></html>
HTML, 200, ['Content-Type' => 'text/html']),
            $imageUrl => Http::response($image, 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $kliping = Kliping::create([
            'judul' => 'Kliping Gambar Hilang',
            'media' => 'Media Sumsel',
            'tanggal' => '2026-07-20',
            'sentimen' => 'netral',
            'status' => 'draft',
            'file_path' => 'uploads/kliping/file-lama-hilang.jpg',
            'url' => $url,
        ]);

        $this->actingAs($operator)
            ->get(route('secure-files.kliping', $kliping))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $newPath = $kliping->refresh()->file_path;
        $this->assertNotSame('uploads/kliping/file-lama-hilang.jpg', $newPath);
        Storage::disk('local')->assertExists($newPath);
    }
}
