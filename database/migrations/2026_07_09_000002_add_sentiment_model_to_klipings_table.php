<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (! Schema::hasColumn('klipings', 'sentimen_model')) {
                $table->string('sentimen_model')->nullable()->after('sentimen_metode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            if (Schema::hasColumn('klipings', 'sentimen_model')) {
                $table->dropColumn('sentimen_model');
            }
        });
    }
};
