<?php

namespace App\Http\Controllers\Cars;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HotelsController extends Controller
{

    protected mixed $amadeusApiKey;
    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public function __construct()
    {
        $this->amadeusApiKey = env('AMADEUS_API_KEY');
        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
    }
    public function hotelById(Request $request): JsonResponse
    {
        try{
            $hotelIds  = $request->query('hotelIds');//getting the offerId from query string
//            if (!is_array($hotelIds) || empty($hotelIds)) {
//                return response()->json(['error' => 'Invalid or missing hotelIds'], 400);
//            }
//            $hotelIdsQuery = implode(',', $hotelIds); //converted into a comma-separated string to match the format expected in the URL query parameter

            $accessToken = $this->getAccessToken(); // Retrieving access token

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/hotels/by-hotels', [
                'hotelIds' => $hotelIds
            ]);

            Log::info('Amadeus Hotel Search Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to search hotels by ID',
                    'details' => $response->json(),
                ], $response ->status());
            }
            return response()->json($response->json());

        }catch (\Exception $e){
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to search hotels by ID', 'message' => $e->getMessage()], 500);
        }
    }


    public function hotelByCity(Request $request): JsonResponse
    {
        try{
            $cityCode = $request->query('cityCode');

//            $request->validate([
//                'location' => 'required|string',
//                'max_results' => 'integer|nullable',
//            ]);

            $accessToken = $this->getAccessToken(); // Retrieving access token

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/hotels/by-city', [
                'cityCode' => $cityCode
            ]);

            Log::info('Amadeus Hotel Search by City Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to search hotels by city',
                    'details' => $response->json(),
                ], $response ->status());
            }
            return response()->json($response->json());

        }catch (\Exception $e){
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to search hotels by city', 'message' => $e->getMessage()], 500);
        }
    }

    public function hotelByGeocode(Request $request): JsonResponse
    {
        try {
            $latitude = $request->query('latitude');
            $longitude = $request->query('longitude');

            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/hotels/by-geocode', [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);

            Log::info('Amadeus Hotel Search by Geocode Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to search hotels by geocode',
                    'details' => $response->json(),
                ], $response->status());
            }

            return response()->json($response->json());
        }catch(\Exception $e){
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to search hotels by geocode', 'message' => $e->getMessage()], 500);
        }
    }

    private function getAccessToken()
    {
        try {
            $response = Http::timeout(50)
                ->asForm()
                ->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->amadeusApiKey,
                'client_secret' => $this->amadeusApiSecret,
            ]);

            $data = $response->json();

            Log::info('Amadeus API token response', $data);

            if (isset($data['access_token'])) {
                return $data['access_token'];
            } else {
                throw new \Exception('Access token not found in response');
            }
        } catch (\Exception $e) {
            Log::error('Error fetching access token: ' . $e->getMessage());
            throw new \Exception('Error fetching access token: ' . $e->getMessage());
        }
    }
}
