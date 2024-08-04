<?php

namespace App\Http\Controllers\Cars;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DestinationController extends Controller
{
    protected mixed $amadeusApiKey;
    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';


    public function __construct() {
        $this->amadeusApiKey = env('AMADEUS_API_KEY');
        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
    }

    /**
     * Retrieving points of interest.
     *
     * @param Request $request
     * @return JsonResponse
     */

//    public function PointsOfInterest(Request $request): JsonResponse
//    {
//        try{
//            $latitude = $request->input('latitude');
//            $longitude = $request->input('longitude');
//
//            $accessToken = $this->getAccessToken();
//
//            $response = Http::withHeaders([
//                'Authorization' => 'Bearer ' . $accessToken,
//            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/pois', [
//                'latitude' => $latitude,
//                'longitude' => $longitude,
//            ]);
//            Log::info('Amadeus POI API Response',[
//                'status' => $response->status(),
//                'body' => $response->body(),
//            ]);
//            if ($response->failed()) {
//                return response()->json([
//                    'error' => 'Failed to retrieve points of interest data',
//                    'details' => $response->json(),
//                ], $response->status());
//            }
//            return response()->json($response->json());
//        } catch (\Exception $e) {
//            return response()->json(['error' => 'Failed to fetch points of interest data', 'message' => $e->getMessage()], 500);
//        }
//    }



    /**
     *\\Validates the location
     * \\fetches coordinates for the given location
     * \\categories,SIGHTS, NIGHTLIFE, RESTAURANT, SHOPPING
     * limit,offset optional params for filtering
     * \\Fetch Points of Interest Using the retrieved coordinates
     * \\landmarks,tourist attractions,historical sites
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function PointsOfInterest(Request $request): JsonResponse
    {
        try{
            $request->validate([
                'location' => 'required|string',
                'categories' => 'array|nullable',
                'limit' => 'integer|nullable',
                'offset' => 'integer|nullable',
            ]);

            $accessToken = $this->getAccessToken();
            $location = $request->input('location');
            $categories = $request->input('categories', []);
            $limit = $request->input('limit',10);
            $offset = $request->input('offset',0);

            // Get latitude and longitude from location
            $locationResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl .'v1/reference-data/locations/cities', [
                'keyword' => $location,
            ]);

            $locationData = $locationResponse->json();
            if (empty($locationData['data'])) {
                return response()->json(['error' => 'Location not found'], 404);
            }

            $city = $locationData['data'][0]; //the first result is the best match
            $latitude = $city['geoCode']['latitude'];
            $longitude = $city['geoCode']['longitude'];

            $poiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl .'v1/reference-data/locations/pois', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'categories' => implode(',', $categories),
                'page[limit]' => $limit,
                'page[offset]' => $offset,
            ]);

            Log::info('Amadeus POI API Response', [
                'status' => $poiResponse->status(),
                'body' => $poiResponse->body(),
            ]);

            if ($poiResponse->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve points of interest data',
                    'details' => $poiResponse->json(),
                ], $poiResponse->status());
            }
            return response()->json($poiResponse->json());
        }catch (\Exception $e){
            return response()->json(['error' => 'Failed to fetch points of interest data', 'message' => $e->getMessage()], 500);
        }

    }


    public  function  PointsOfInterestBySquare(Request $request): JsonResponse
    {
        try{
            $north = $request->input('north');
            $south = $request->input('south');
            $west = $request->input('west');
            $east = $request->input('east');

            //ensuring all params r present
            if (!$north || !$west || !$south || !$east) {
                return response()->json([
                    'error' => 'Missing required parameters: north, west, south, east.',
                ], 400);
            }

            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/pois/by-square', [
                'north' => $north,
                'south' => $south,
                'west' => $west,
                'east' => $east,
            ]);
            Log::info('Amadeus POI API Response',[
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve points of interest by square data',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch points of interest by square', 'message' => $e->getMessage()], 500);
        }
    }

//
//"poisId": "poi:12345"
//


    public function PointsOfInterestById($poisId): JsonResponse
    {
        try{
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get ( $this->amadeusApiUrl . 'v1/reference-data/locations/pois/'. $poisId);

            Log::info('Amadeus POI API Response',[
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve tour activities data',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch tour activities data', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * @throws \Exception
     * \\user enters location name, lats n longs r fetched from location name
     * \\get activities using lats n longs
     * guided tours, excursions, and specific activities like cooking classes,
     */
    public function getToursAndActivities(Request $request): JsonResponse
    {
        $request->validate([
            'location' => 'required|string',
            'max_results' => 'integer|nullable',
        ]);
        $accessToken = $this->getAccessToken();

        $location = $request->query('location');
        $maxResults = $request->query('max_results', 10); //Default to 10 if not provided

        try {
            // Get latitude and longitude from location
            $locationResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl .'v1/reference-data/locations/cities', [
                'keyword' => $location,
            ]);

            $locationData = $locationResponse->json();
            if (empty($locationData['data'])) {
                return response()->json(['error' => 'Location not found'], 404);
            }

            $city = $locationData['data'][0]; //the first result is the best match
            $latitude = $city['geoCode']['latitude'];
            $longitude = $city['geoCode']['longitude'];

            // Get activities using latitude and longitude
            $activitiesResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl .'v1/shopping/activities', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'max' => $maxResults,
            ]);

            $activities = $activitiesResponse->json();
            Log::info($activities);

            return response()->json($activities);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }


    public function getToursAndActivitiesByBoundingBox(Request $request): JsonResponse
    {
        try{
            $north = $request->input('north');
            $south = $request->input('south');
            $west  = $request->input('west');
            $east  = $request->input('east');
            //ensuring all params r present
            if (!$north || !$west || !$south || !$east) {
                return response()->json([
                    'error' => 'Missing required parameters: north, west, south, east.',
                ], 400);
            }

            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl . 'v1/shopping/activities/by-square', [
                'north' => $north,
                'south' => $south,
                'east' => $east,
                'west' => $west,
            ]);

            Log::info('Amadeus Activities by Bounding Box API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve tour activities data',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());
        }catch (\Exception $e){
            return response()->json(['error' => 'Failed to fetch tour activities data', 'message' => $e->getMessage()], 500);}
    }

//
//"activityId": "activity:12345"
//

    public function getToursAndActivitiesById($activityId): JsonResponse
    {
        try{
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl . 'v1/shopping/activities/'. $activityId);

            Log::info('Amadeus Activity Details API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve tour activities data',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());
        }catch (\Exception $e){
            return response()->json(['error' => 'Failed to retrieve activity', 'message' => $e->getMessage()], 500);
        }
    }


//
//"keyword": "Nairobi",nai,par,dub,bei etc//*required
//"countryCode": "KE",fr,tz
//"max": 10,no of results
//"include": "AIRPORTS"//other resources
//

    public function searchCity(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');
            $countryCode = $request->input('countryCode');
            $max = $request->input('max', 10);//default to 10
            $include = $request->input('include');

            //validate required keyword parameter
            if (empty($keyword)) {
                return response()->json(['error' => 'Keyword is required'], 400);
            }

            $accessToken = $this->getAccessToken();

            //query params
            $queryParams = [
                'keyword' => $keyword,
                'max' => $max,
            ];
            if ($countryCode) {
                $queryParams['countryCode'] = $countryCode;
            }

            // Include parameter handling
            if (!empty($include)) {
                //valid include options n convert to upperCase
                $validIncludes = ['AIRPORTS']; //add other valid options if applicable
                $includeArray = explode(',', $include); //converts include string to array
                $filteredIncludes = array_filter($includeArray, function ($value) use ($validIncludes) {
                    return in_array(strtoupper(trim($value)), $validIncludes);
                });

                if (!empty($filteredIncludes)) {
                    $queryParams['include'] = implode(',', $filteredIncludes);
                }
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/cities', $queryParams);

            Log::info('Amadeus City Search API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Checking if response fails
            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve cities data',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to search city', 'message' => $e->getMessage()], 500);
        }
    }


    public function searchCountryCities(Request $request): JsonResponse
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input. Country name is required',
                'details' => $validator->errors()
            ], 400);
        }

        // Get the country name from the request
        $country = $request->input('country');
        $limit = $request->input('limit', 10);//default to 10 if not provided


        try {
            // Retrieve all cities for the given country
            $cities = City::where('country', $country)
                ->limit($limit)->get();

            // Check if cities were found
            if ($cities->isEmpty()) {
                return response()->json([
                    'message' => 'No cities found for the provided country.'
                ], 404);
            }

            // Return the cities as a response
            return response()->json($cities);

        } catch (\Exception $e) {
            // Handle any other errors (e.g., database issues)
            return response()->json([
                'error' => 'An error occurred while retrieving cities.',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    public function destinationExperiencePage(Request $request): JsonResponse
    {
        try{
            //getting users ip address
            $ipAddress = $request->ip();

            $accessToken = $this->getAccessToken();

            //SEnd request to IP STack
            $client = new Client();
            $apiKey = env('IP_STACK_API_KEY');
            $response = $client->get("http://api.ipstack.com/{$ipAddress}?access_key={$apiKey}");
            $locationData = json_decode($response->getBody(), true);

            if ($locationData->failed()) {
                return response()->json(['error' => 'Unable to determine location'], 500);
            }

            //Extracting the coordinates
            $latitude = $locationData['latitude'];
            $longitude = $locationData['longitude'];

            //fetching tours and activities
            $toursResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl . 'v1/shopping/activities', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'max' => 10, // Default to 10 results
            ]);

            //fetching points of interest
            $poiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/pois', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'page[limit]' => 10, // Default to 10 results
            ]);

            //return result of tours n activities,POIs
            return response()->json([
                'tours_and_activities' => $toursResponse->json(),
                'points_of_interest' => $poiResponse->json(),
            ]);
        }catch (\Exception $e){
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }


    private function getAccessToken()
    {
        try {
            $response = Http::asForm()->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
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


//    private function getAccessToken()
//    {
//        try {
//            $response = Http::asForm()->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
//                'grant_type' => 'client_credentials',
//                'client_id' => $this->amadeusApiKey,
//                'client_secret' => $this->amadeusApiSecret,
//            ]);
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

}
