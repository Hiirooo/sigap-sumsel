<?php

namespace Tests\Feature;

use App\Models\RilisBerita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RilisBeritaStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_is_automatically_archived_based_on_publication_status(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $rilis = RilisBerita::create([
            'judul' => 'Rilis Status',
            'slug' => 'rilis-status',
            'isi' => '<p>Isi berita.</p>',
            'tanggal_rilis' => '2026-07-14',
            'status' => 'draft',
        ]);

        foreach ([['terpublikasi', true], ['draft', false]] as [$expectedStatus, $expectedArchive]) {
            $this->actingAs($operator)
                ->post(route('rilis-berita.toggle-status', $rilis))
                ->assertRedirect();

            $this->assertSame($expectedStatus, $rilis->refresh()->status);
            $this->assertSame($expectedArchive, $rilis->is_archived);
        }
    }
}
