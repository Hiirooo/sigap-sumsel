<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (! Schema::hasColumn('klipings', 'terkait_pimpinan')) {
                $table->boolean('terkait_pimpinan')->default(false)->after('sentimen_otomatis');
            }

            if (! Schema::hasColumn('klipings', 'kata_kunci_keterkaitan')) {
                $table->string('kata_kunci_keterkaitan')->nullable()->after('terkait_pimpinan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (Schema::hasColumn('klipings', 'kata_kunci_keterkaitan')) {
                $table->dropColumn('kata_kunci_keterkaitan');
            }

            if (Schema::hasColumn('klipings', 'terkait_pimpinan')) {
                $table->dropColumn('terkait_pimpinan');
            }
        });
    }
};
