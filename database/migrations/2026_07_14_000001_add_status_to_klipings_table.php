<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('sentimen');
            $table->index(['status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::table('klipings', function (Blueprint $table) {
            $table->dropIndex(['status', 'tanggal']);
            $table->dropColumn('status');
        });
    }
};
