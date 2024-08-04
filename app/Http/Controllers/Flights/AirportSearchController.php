<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Airline;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class AirportSearchController extends Controller
{
    protected $httpClient;
    protected $amadeusApiKey;
    protected $amadeusApiSecret;
    protected $amadeusApiUrl = 'https://test.api.amadeus.com/v1/';

    public function __construct()//a special func that runs wen an object of this class is created
    {
        $this->httpClient = new Client();
        $this->amadeusApiKey = env('AMADEUS_API_KEY');
        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
    }


    public function index()
    {
        $airports = Airport::all();
        return response()->json($airports);
    }

    public function search(Request $request): JsonResponse
    {
        //search query from the request
        $query = $request->get('q');

        // First, try to find an exact match by IATA code
        $exactMatch = Airport::where('code', $query)->get();

        //returning the match
        if ($exactMatch->isNotEmpty()) {
            return response()->json($exactMatch);
        }

        //if not, performs a broader search
        $airports = Airport::where('airport', 'like', '%' . $query . '%')
            ->orWhere('city', 'like', '%' . $query . '%')
            ->orWhere('country', 'like', '%' . $query . '%')
            ->orWhere('code', 'like', '%' . $query . '%')
            ->get();

        return response()->json($airports);
    }

    public function airlineSearch(Request $request): JsonResponse
    {
        // Search query from the request
        $name = $request->get('name');
        $country = $request->get('country');
        $code = $request->get('code');

        $airlines = Airline::query();

        if ($name) {
            $airlines->where('name', 'like', '%' . $name . '%');
        }

        if ($country) {
            $airlines->where('country', 'like', '%' . $country . '%');
        }

        if ($code) {
            $airlines->where('code', $code);
        }
        // Executing the query
        $results = $airlines->get();

        if ($results->isEmpty()) {
            return response()->json(['error' => 'No airlines found'], 404);
        }

        // Returns the result as JSON
        return response()->json($results);
    }

    public function nearest(Request $request): JsonResponse
    {
        $location = $request->get('location');
        $latitude = $request->get('lat');
        $longitude = $request->get('long');

        if ($location) {
            // Geocoding the location to get lat and long
            try {
                $geoResponse = $this->httpClient->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', [
                    'query' => [
                        'address' => $location,
                        'key' => env('GOOGLE_MAPS_API_KEY'),
                    ],
                ]);

                $geoData = json_decode($geoResponse->getBody()->getContents(), true);

                if (empty($geoData['results'])) {
                    return response()->json(['error' => 'Location not found'], 404);
                }

                $latitude = $geoData['results'][0]['geometry']['location']['lat'];
                $longitude = $geoData['results'][0]['geometry']['location']['lng'];
            } catch (\Exception $e) {
                Log::error('Error fetching geocoding data: ' . $e->getMessage());
                return response()->json(['error' => 'Failed to fetch geocoding data'], 500);
            }
        }

        if (!$latitude || !$longitude) {
            return response()->json(['error' => 'Latitude and longitude or location are required'], 400);
        }

        $cacheKey = "nearest_airports_{$latitude}_{$longitude}";

        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        try {
            $response = $this->httpClient->request('GET', $this->amadeusApiUrl . 'reference-data/locations/airports', [
                'query' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => 50,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            Cache::put($cacheKey, $data, 600);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching nearest airports data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch nearest airports data'], 500);
        }
    }

    private function getAccessToken()
    {
        $response = $this->httpClient->request('POST', 'https://test.api.amadeus.com/v1/security/oauth2/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->amadeusApiKey,
                'client_secret' => $this->amadeusApiSecret,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['access_token'];
    }

}

