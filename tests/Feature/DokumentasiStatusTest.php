<?php

namespace Tests\Feature;

use App\Models\Dokumentasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokumentasiStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_toggle_documentation_verification_and_archive_status(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $dokumentasi = Dokumentasi::create([
            'judul' => 'Dokumentasi Status',
            'tanggal' => '2026-07-14',
            'jenis_media' => 'foto',
            'file_path' => 'uploads/dokumentasi/status.jpg',
            'status_verifikasi' => 'draft',
            'status_digitalisasi' => 'belum_didigitalisasi',
        ]);

        foreach (['terverifikasi', 'draft'] as $expectedStatus) {
            $this->actingAs($operator)
                ->post(route('dokumentasi.toggle-status', $dokumentasi))
                ->assertRedirect();

            $this->assertSame($expectedStatus, $dokumentasi->refresh()->status_verifikasi);
        }

        $this->actingAs($operator)
            ->post(route('dokumentasi.toggle-archive', $dokumentasi))
            ->assertRedirect();
        $this->assertTrue($dokumentasi->refresh()->is_archived);

        $this->actingAs($operator)
            ->post(route('dokumentasi.toggle-archive', $dokumentasi))
            ->assertRedirect();
        $this->assertFalse($dokumentasi->refresh()->is_archived);
    }
}
