<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (! Schema::hasColumn('klipings', 'persentase_keterkaitan')) {
                $table->unsignedTinyInteger('persentase_keterkaitan')->nullable()->after('terkait_pimpinan');
            }

            if (! Schema::hasColumn('klipings', 'tingkat_keterkaitan')) {
                $table->string('tingkat_keterkaitan', 30)->nullable()->after('persentase_keterkaitan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (Schema::hasColumn('klipings', 'tingkat_keterkaitan')) {
                $table->dropColumn('tingkat_keterkaitan');
            }

            if (Schema::hasColumn('klipings', 'persentase_keterkaitan')) {
                $table->dropColumn('persentase_keterkaitan');
            }
        });
    }
};
