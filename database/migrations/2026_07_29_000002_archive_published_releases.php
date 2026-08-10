<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rilis_beritas')
            ->where('status', 'terpublikasi')
            ->update(['is_archived' => true]);

        DB::table('rilis_beritas')
            ->where('status', 'draft')
            ->update(['is_archived' => false]);
    }

    public function down(): void
    {
        // The previous archive state cannot be reconstructed safely.
    }
};
