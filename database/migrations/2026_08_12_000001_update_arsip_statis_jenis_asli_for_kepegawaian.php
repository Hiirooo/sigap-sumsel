<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("UPDATE arsip_statis SET jenis_asli = 'cuti' WHERE jenis_asli NOT IN ('cuti', 'kenaikan_pangkat', 'berkala')");
            DB::statement("ALTER TABLE arsip_statis MODIFY jenis_asli ENUM('cuti', 'kenaikan_pangkat', 'berkala') NOT NULL DEFAULT 'cuti'");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE arsip_statis MODIFY jenis_asli ENUM('fisik', 'cetak', 'cd', 'lainnya') NOT NULL DEFAULT 'fisik'");
        }
    }
};
