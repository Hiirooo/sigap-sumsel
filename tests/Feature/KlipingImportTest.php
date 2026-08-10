<?php

namespace Tests\Feature;

use App\Models\Kliping;
use App\Models\User;
use App\Services\ArticleSentimentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class KlipingImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_import_an_online_article_from_url(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $url = 'https://palpos.bacakoran.co/read/45637/contoh-berita';
        $this->mock(ArticleSentimentService::class, function (MockInterface $mock) use ($url) {
            $mock->shouldReceive('analyzeUrl')->once()->with($url)->andReturn([
                'title' => 'Herman Deru Hadiri Kegiatan',
                'media' => 'Palpos',
                'published_at' => '2026-07-20',
                'content' => 'Gubernur Sumsel Herman Deru menghadiri kegiatan pelayanan masyarakat.',
                'image_url' => null,
                'sentimen' => 'positif',
                'confidence' => 88,
                'sentimen_metode' => 'rule_based',
                'terkait_pimpinan' => true,
                'persentase_keterkaitan' => 90,
                'tingkat_keterkaitan' => 'tinggi',
                'kata_kunci_keterkaitan' => 'Herman Deru',
            ]);
        });

        $this->actingAs($operator)
            ->postJson(route('kliping.import-url'), ['url' => $url, 'status' => 'draft'])
            ->assertCreated()
            ->assertJsonPath('status', 'created');

        $this->assertDatabaseHas('klipings', [
            'url' => $url,
            'judul' => 'Herman Deru Hadiri Kegiatan',
            'media' => 'Palpos',
            'status' => 'draft',
        ]);
    }

    public function test_import_skips_an_existing_url_without_analyzing_it_again(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $url = 'https://palpres.bacakoran.co/read/56812/contoh-berita';
        Kliping::create([
            'judul' => 'Kliping Lama',
            'media' => 'Palpres',
            'tanggal' => '2026-07-20',
            'url' => $url,
            'sentimen' => 'netral',
            'status' => 'draft',
        ]);
        $this->mock(ArticleSentimentService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('analyzeUrl');
        });

        $this->actingAs($operator)
            ->postJson(route('kliping.import-url'), ['url' => $url.'/', 'status' => 'draft'])
            ->assertOk()
            ->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, Kliping::where('url', $url)->count());
    }

    public function test_import_rejects_an_article_that_is_not_relevant(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $url = 'https://example.com/berita-lokal';
        $this->mock(ArticleSentimentService::class, function (MockInterface $mock) {
            $mock->shouldReceive('analyzeUrl')->once()->andReturn([
                'title' => 'Berita Pemerintah Kota',
                'media' => 'Media Lokal',
                'published_at' => '2026-07-20',
                'content' => 'Kegiatan pemerintah kota tanpa keterkaitan dengan pemerintah provinsi.',
                'sentimen' => 'netral',
                'confidence' => 70,
                'terkait_pimpinan' => false,
                'persentase_keterkaitan' => 20,
                'tingkat_keterkaitan' => 'tidak_terkait',
            ]);
        });

        $this->actingAs($operator)
            ->postJson(route('kliping.import-url'), ['url' => $url, 'status' => 'draft'])
            ->assertUnprocessable()
            ->assertJsonPath('status', 'failed');

        $this->assertDatabaseCount('klipings', 0);
    }
}
