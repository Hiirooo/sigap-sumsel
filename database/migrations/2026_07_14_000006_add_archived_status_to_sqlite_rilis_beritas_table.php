<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('rilis_beritas', function (Blueprint $table) {
                $table->enum('status', ['draft', 'terpublikasi', 'diarsipkan'])->default('draft')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::table('rilis_beritas')->where('status', 'diarsipkan')->update(['status' => 'draft']);

            Schema::table('rilis_beritas', function (Blueprint $table) {
                $table->enum('status', ['draft', 'terpublikasi'])->default('draft')->change();
            });
        }
    }
};
