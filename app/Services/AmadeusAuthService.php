<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AmadeusAuthService
{
    private static $accessToken = null;
    private static $expiryTime = null;

    public static function getAccessToken()
    {
        //checking if the token is still valid
        if (self::$accessToken && self::$expiryTime > time()) {
            return self::$accessToken;
        }

        //checking if they are being retrieved correctly
//        Log::info('Amadeus API Key:', ['key' => env('AMADEUS_API_KEY')]);
//        Log::info('Amadeus API Secret:', ['secret' => env('AMADEUS_API_SECRET')]);

        //else generate a new token
        $client = new Client();
        $response = $client->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => env('AMADEUS_API_KEY'),
                'client_secret' => env('AMADEUS_API_SECRET'),
            ],
        ]);
//        Log::info('Request Headers', $response->getHeaders());

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['access_token'])) {
            self::$accessToken = $data['access_token'];
            self::$expiryTime = time() + $data['expires_in']; //'expires_in' is in seconds
            return self::$accessToken;
        } else {
            throw new \Exception('Access token not found in response');
        }
    }
}
