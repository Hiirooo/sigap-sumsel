<?php

namespace App\Providers;

use As247\Flysystem\GoogleDrive\GoogleDriveAdapter;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('instagram-upload', function (Request $request) {
            $unlimitedIps = config('services.instagram_upload.unlimited_ips', []);

            return in_array($request->ip(), $unlimitedIps, true)
                ? Limit::none()
                : Limit::perMinute(120)->by($request->ip());
        });

        \App\Models\RilisBerita::observe(\App\Observers\ModelObserver::class);
        \App\Models\Dokumentasi::observe(\App\Observers\ModelObserver::class);
        \App\Models\Kliping::observe(\App\Observers\ModelObserver::class);
        \App\Models\ArsipStatis::observe(\App\Observers\ModelObserver::class);
        \App\Models\KategoriKegiatan::observe(\App\Observers\ModelObserver::class);
        \App\Models\MonevChecklist::observe(\App\Observers\ModelObserver::class);

        Storage::extend('google-drive', function ($app, $config) {
            $googleConfig = config('google-drive');

            $client = new Google_Client();
            $client->setClientId($googleConfig['client_id']);
            $client->setClientSecret($googleConfig['client_secret']);
            $client->setScopes([
                Google_Service_Drive::DRIVE,
                Google_Service_Drive::DRIVE_FILE,
            ]);

            if ($refreshToken = $googleConfig['refresh_token']) {
                $client->fetchAccessTokenWithRefreshToken($refreshToken);
            }

            $service = new Google_Service_Drive($client);
            $adapter = new GoogleDriveAdapter($service, [
                'root' => $config['root'] ?? 'root',
            ]);

            $filesystem = new Filesystem($adapter);

            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}
