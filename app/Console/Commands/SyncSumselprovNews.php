<?php

namespace App\Console\Commands;

use App\Services\SumselprovNewsImporter;
use Illuminate\Console\Command;

class SyncSumselprovNews extends Command
{
    protected $signature = 'app:sync-sumselprov-news {--pages= : Jumlah maksimum halaman yang diambil} {--endpoint= : Endpoint API sumselprov, lihat SUMSELPROV_API_ENDPOINTS di .env}';

    protected $description = 'Sinkronkan rilis berita dari API Sumselprov ke SIGAP Sumsel';

    public function handle(SumselprovNewsImporter $importer): int
    {
        $pages = $this->option('pages') !== null ? (int) $this->option('pages') : null;
        $endpoint = $this->option('endpoint') ?: null;
        $result = $importer->import($pages, $endpoint);

        $this->info(sprintf(
            'Sinkronisasi selesai: %d baru, %d diperbarui, %d dilewati, %d gagal.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
