<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RilisBerita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_access_content_management_routes(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer)
            ->get(route('rilis-berita.index'))
            ->assertForbidden();
    }

    public function test_operator_can_access_content_management_routes(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->get(route('rilis-berita.index'))
            ->assertOk();
    }

    public function test_operator_cannot_access_admin_only_routes(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->get(route('kategori-kegiatan.index'))
            ->assertForbidden();
    }

    public function test_release_index_is_paginated_to_fifteen_rows(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        foreach (range(1, 20) as $index) {
            RilisBerita::create([
                'judul' => "Rilis {$index}",
                'slug' => "rilis-{$index}",
                'isi' => 'Isi berita',
                'tanggal_rilis' => '2026-07-14',
                'penulis' => 'Humas',
                'status' => 'draft',
            ]);
        }

        $this->actingAs($operator)
            ->get(route('rilis-berita.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('RilisBerita/Index')
                ->has('rilisBerita.data', 15)
                ->where('rilisBerita.total', 20)
                ->where('rilisBerita.last_page', 2));
    }
}
