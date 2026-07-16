<?php

namespace Tests\Feature;

use App\Models\RilisBerita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RilisBeritaStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_cycle_release_publication_status(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $rilis = RilisBerita::create([
            'judul' => 'Rilis Status',
            'slug' => 'rilis-status',
            'isi' => '<p>Isi berita.</p>',
            'tanggal_rilis' => '2026-07-14',
            'status' => 'draft',
        ]);

        foreach (['terpublikasi', 'diarsipkan', 'draft'] as $expectedStatus) {
            $this->actingAs($operator)
                ->post(route('rilis-berita.toggle-status', $rilis))
                ->assertRedirect();

            $this->assertSame($expectedStatus, $rilis->refresh()->status);
        }
    }
}
