<?php

namespace App\Console\Commands;

use Google_Client;
use Illuminate\Console\Command;

class GoogleDriveAuth extends Command
{
    protected $signature = 'google:auth';
    protected $description = 'OAuth 2.0 authorization for Google Drive';

    public function handle()
    {
        $config = config('google-drive');

        $client = new Google_Client();
        $client->setClientId($config['client_id']);
        $client->setClientSecret($config['client_secret']);
        $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([
            \Google_Service_Drive::DRIVE,
            \Google_Service_Drive::DRIVE_FILE,
        ]);

        $authUrl = $client->createAuthUrl();

        $this->info('1. Open this URL in your browser:');
        $this->line($authUrl);
        $this->newLine();
        $this->warn('2. Log in with your Google account (zptid100@gmail.com)');
        $this->warn('3. Grant access to Google Drive');
        $this->newLine();
        $code = $this->ask('4. Paste the authorization code here');

        if (! $code) {
            $this->error('No code provided.');
            return 1;
        }

        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($accessToken['error'])) {
            $this->error('Error: ' . ($accessToken['error_description'] ?? $accessToken['error']));
            return 1;
        }

        $refreshToken = $client->getRefreshToken();

        if (! $refreshToken) {
            $this->error('No refresh token received. Make sure you use a fresh authorization.');
            return 1;
        }

        // Simpan ke .env
        $envPath = base_path('.env');
        $env = file_get_contents($envPath);

        $escaped = str_replace('"', '\\"', $refreshToken);

        if (preg_match('/^GOOGLE_DRIVE_REFRESH_TOKEN=.*$/m', $env)) {
            $env = preg_replace('/^GOOGLE_DRIVE_REFRESH_TOKEN=.*$/m', 'GOOGLE_DRIVE_REFRESH_TOKEN=' . $escaped, $env);
        } else {
            $env .= "\nGOOGLE_DRIVE_REFRESH_TOKEN=" . $escaped . "\n";
        }

        file_put_contents($envPath, $env);

        $this->info('Refresh token saved to .env successfully!');
        $this->line('Run: php artisan config:clear');
    }
}
