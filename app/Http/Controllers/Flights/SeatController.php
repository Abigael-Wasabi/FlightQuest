<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Services\AmadeusAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeatController extends Controller
{
//    protected mixed $amadeusApiKey;
//    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public function __construct()
    {
//        $this->amadeusApiKey = env('AMADEUS_API_KEY');
//        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
//        Log::info('Amadeus API Key:', ['key' => env('AMADEUS_API_KEY')]);
//        Log::info('Amadeus API Secret:', ['secret' => env('AMADEUS_API_SECRET')]);
//
//
//        if (is_null($this->amadeusApiKey) || is_null($this->amadeusApiSecret)) {
//            throw new \Exception('Amadeus API Key or Secret is missing from environment variables.');
//        }
    }

    /**
     * Retrieve the seat map for a flight offer.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function getSeatMap(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'flightOffers' => 'required|array',
            ]);
            Log::info($request);

            $flightOffers = $request->input('flightOffers');

            //retrieving access token
            $accessToken = AmadeusAuthService::getAccessToken();

            //Making an API request to Amadeus
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($this->amadeusApiUrl . 'v1/shopping/seatmaps', [
                'data' => $flightOffers
            ]);

            //Logging the response for debugging
            Log::info('Amadeus Seatmap API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve seat map',
                    'details' => $response->json()
                ], $response->status());
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch seat map', 'message' => $e->getMessage()], 500);
        }
    }

//    private function getAccessToken()
//    {
//        try {
//            $response = Http::timeout(50)
//                ->asForm()
//                ->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
//                    'grant_type' => 'client_credentials',
//                    'client_id' => $this->amadeusApiKey,
//                    'client_secret' => $this->amadeusApiSecret,
//                ]);
//
//            if ($response->successful()) {
//                $data = $response->json();
//                Log::info('Amadeus API token response', $data);
//
//                if (isset($data['access_token'])) {
//                    return $data['access_token'];
//                } else {
//                    Log::error('Access token not found in response', $data);
//                    throw new \Exception('Access token not found in response');
//                }
//            } else {
//                Log::error('Error fetching access token, HTTP status: ' . $response->status(), [
//                    'response' => $response->body(),
//                ]);
//                throw new \Exception('Error fetching access token, HTTP status: ' . $response->status());
//            }
//        } catch (\Exception $e) {
//            Log::error('Exception caught while fetching access token: ' . $e->getMessage(), [
//                'exception' => $e,
//            ]);
//            throw new \Exception('Exception caught while fetching access token: ' . $e->getMessage());
//        }
//    }


//    private function getAccessToken()
//    {
//        try {
//            // Make the request to get the access token
//            $response = Http::timeout(50)
//                ->asForm()
//                ->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
//                    'grant_type' => 'client_credentials',
//                    'client_id' => $this->amadeusApiKey,
//                    'client_secret' => $this->amadeusApiSecret,
//                ]);
//
//            Log::info('Request sent to Amadeus:', [
//                'url' => 'https://test.api.amadeus.com/v1/security/oauth2/token',
//                'params' => [
//                    'grant_type' => 'client_credentials',
//                    'client_id' => $this->amadeusApiKey,
//                    'client_secret' => $this->amadeusApiSecret,
//                ]
//            ]);
//
//            if ($response->successful()) {
//                $data = $response->json();
//                Log::info('Amadeus API token response', $data);
//                Log::info('Response from Amadeus:', [
//                    'status' => $response->status(),
//                    'body' => $response->body()
//                ]);
//
//                if (isset($data['access_token'])) {
//                    return $data['access_token'];
//                } else {
//                    Log::error('Access token not found in response', $data);
//                    throw new \Exception('Access token not found in response');
//                }
//            } else {
//                Log::error('Error fetching access token, HTTP status: ' . $response->status(), [
//                    'response' => $response->body(),
//                ]);
//                throw new \Exception('Error fetching access token, HTTP status: ' . $response->status());
//            }
//        } catch (\Exception $e) {
//            Log::error('Exception caught while fetching access token: ' . $e->getMessage(), [
//                'exception' => $e,
//            ]);
//            throw new \Exception('Exception caught while fetching access token: ' . $e->getMessage());
//        }
//    }

}
