<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rilis_beritas', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->index()->after('status');
        });
        Schema::table('klipings', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->index()->after('status');
        });
        Schema::table('dokumentasis', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->index()->after('status_digitalisasi');
        });

        DB::table('rilis_beritas')->where('status', 'diarsipkan')->update([
            'status' => 'terpublikasi',
            'is_archived' => true,
        ]);
        DB::table('klipings')->where('status', 'diarsipkan')->update([
            'status' => 'terpublikasi',
            'is_archived' => true,
        ]);
        DB::table('dokumentasis')->where('status_digitalisasi', 'sudah_diarsipkan')->update([
            'status_digitalisasi' => 'sudah_didigitalisasi',
            'is_archived' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('rilis_beritas', fn (Blueprint $table) => $table->dropColumn('is_archived'));
        Schema::table('klipings', fn (Blueprint $table) => $table->dropColumn('is_archived'));
        Schema::table('dokumentasis', fn (Blueprint $table) => $table->dropColumn('is_archived'));
    }
};
