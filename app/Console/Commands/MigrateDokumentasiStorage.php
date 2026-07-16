<?php

namespace App\Console\Commands;

use App\Models\Dokumentasi;
use App\Models\DokumentasiMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateDokumentasiStorage extends Command
{
    protected $signature = 'storage:migrate-dokumentasi {--from=google-drive} {--to=}';

    protected $description = 'Pindahkan file dokumentasi ke disk yang dikonfigurasi';

    public function handle(): int
    {
        $source = (string) $this->option('from');
        $target = (string) ($this->option('to') ?: config('services.dokumentasi.storage_disk', 'local'));

        if (! in_array($source, ['local', 'google-drive'], true) || ! in_array($target, ['local', 'google-drive'], true)) {
            $this->error('Disk sumber dan tujuan harus local atau google-drive.');
            return self::FAILURE;
        }

        if ($source === $target) {
            $this->info('Disk sumber dan tujuan sama, tidak ada file yang dipindahkan.');
            return self::SUCCESS;
        }

        $moved = 0;
        $skipped = 0;

        $paths = Dokumentasi::query()->get(['file_path', 'thumbnail_path'])
            ->flatMap(fn (Dokumentasi $item) => [$item->file_path, $item->thumbnail_path])
            ->merge(DokumentasiMedia::query()->get(['file_path', 'thumbnail_path'])
                ->flatMap(fn (DokumentasiMedia $item) => [$item->file_path, $item->thumbnail_path]))
            ->filter()
            ->map(fn (string $path) => str_starts_with($path, '/storage/') ? ltrim(substr($path, 9), '/') : $path)
            ->unique();

        $paths->each(function (string $path) use ($source, $target, &$moved, &$skipped) {

            if (Storage::disk($target)->exists($path)) {
                if (Storage::disk($source)->exists($path)) {
                    Storage::disk($source)->delete($path);
                }
                $skipped++;
                return;
            }

            if (! Storage::disk($source)->exists($path)) {
                $skipped++;
                return;
            }

            Storage::disk($target)->put($path, Storage::disk($source)->get($path));

            if (! Storage::disk($target)->exists($path)) {
                throw new \RuntimeException("Verifikasi file gagal: {$path}");
            }

            Storage::disk($source)->delete($path);
            $moved++;
        });

        $this->info("Migrasi dokumentasi selesai: {$moved} dipindahkan, {$skipped} dilewati.");

        return self::SUCCESS;
    }
}
