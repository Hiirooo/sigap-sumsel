<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rilis_beritas MODIFY status ENUM('draft', 'terpublikasi', 'diarsipkan') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('rilis_beritas')->where('status', 'diarsipkan')->update(['status' => 'draft']);
            DB::statement("ALTER TABLE rilis_beritas MODIFY status ENUM('draft', 'terpublikasi') NOT NULL DEFAULT 'draft'");
        }
    }
};
