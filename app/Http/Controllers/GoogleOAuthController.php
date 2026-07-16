<?php

namespace App\Http\Controllers;

use Google_Client;
use Illuminate\Http\Request;

class GoogleOAuthController extends Controller
{
    public function redirect()
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (! $code) {
            return response('Authorization failed: no code received.', 400);
        }

        $client = $this->getClient();
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($accessToken['error'])) {
            return response('Error: ' . ($accessToken['error_description'] ?? $accessToken['error']), 400);
        }

        $refreshToken = $client->getRefreshToken();

        if (! $refreshToken) {
            return response('No refresh token received. Try revoking access at https://myaccount.google.com/permissions and try again.', 400);
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

        return response('Refresh token saved! Run: php artisan config:clear');
    }

    private function getClient(): Google_Client
    {
        $config = config('google-drive');

        $client = new Google_Client();
        $client->setClientId($config['client_id']);
        $client->setClientSecret($config['client_secret']);
        $client->setRedirectUri(route('google.auth.callback'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([
            \Google_Service_Drive::DRIVE,
            \Google_Service_Drive::DRIVE_FILE,
        ]);

        return $client;
    }
}
