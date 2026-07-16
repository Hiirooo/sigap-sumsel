<?php

namespace Tests\Feature;

use App\Models\Kliping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KlipingStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_cycle_kliping_publication_status(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $kliping = Kliping::create([
            'judul' => 'Kliping Status',
            'media' => 'Media Sumsel',
            'tanggal' => '2026-07-14',
            'sentimen' => 'netral',
            'status' => 'draft',
        ]);

        foreach (['terpublikasi', 'diarsipkan', 'draft'] as $expectedStatus) {
            $this->actingAs($operator)
                ->patch(route('kliping.toggle-status', $kliping))
                ->assertRedirect();

            $this->assertSame($expectedStatus, $kliping->refresh()->status);
        }
    }
}
