<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (! Schema::hasColumn('klipings', 'sentimen_metode')) {
                $table->string('sentimen_metode')->nullable()->after('sentimen_otomatis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (Schema::hasColumn('klipings', 'sentimen_metode')) {
                $table->dropColumn('sentimen_metode');
            }
        });
    }
};
