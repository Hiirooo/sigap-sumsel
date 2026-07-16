<?php

namespace App\Console\Commands;

use App\Models\RilisBerita;
use App\Services\RemoteFileCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrewarmRilisImageCache extends Command
{
    protected $signature = 'cache:prewarm-rilis-images {--limit=12}';

    protected $description = 'Isi cache lokal gambar rilis terbaru dari Google Drive';

    public function handle(RemoteFileCache $cache): int
    {
        $limit = max(1, min((int) $this->option('limit'), 100));
        $cached = 0;
        $skipped = 0;

        RilisBerita::where('status', 'terpublikasi')
            ->whereNotNull('gambar_utama')
            ->latest('tanggal_rilis')
            ->take($limit)
            ->get()
            ->each(function (RilisBerita $rilis) use ($cache, &$cached, &$skipped) {
                if ($cache->publicUrl($rilis->gambar_utama)) {
                    $skipped++;
                    return;
                }

                if (Storage::disk('local')->exists($rilis->gambar_utama)) {
                    $cache->publish($rilis->gambar_utama, Storage::disk('local')->get($rilis->gambar_utama));
                    $cached++;
                    return;
                }

                if (Storage::disk('google-drive')->exists($rilis->gambar_utama)) {
                    $cachePath = $cache->remember('google-drive', $rilis->gambar_utama);
                    $cache->publish($rilis->gambar_utama, Storage::disk('local')->get($cachePath));
                    $cached++;
                    return;
                }

                $skipped++;
            });

        $this->info("Prewarm selesai: {$cached} gambar dicache, {$skipped} dilewati.");

        return self::SUCCESS;
    }
}
