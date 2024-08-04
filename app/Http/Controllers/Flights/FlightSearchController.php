<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use App\Models\FlightsSearchModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class FlightSearchController extends Controller
{
    protected mixed $amadeusApiKey;
    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    private $flightSearchModel;
    private $flightsModel;

    public function __construct(FlightsSearchModel $searchModel, Flight $flightsModel)
    {
        $this->amadeusApiKey = env('AMADEUS_API_KEY');
        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
        $this->flightSearchModel = $searchModel;
        $this->flightsModel = $flightsModel;
    }


    public function search($any): JsonResponse
    {
        //$origin, $destination, $departureDate, $adults, $children,
        //        $infants, $travelClass, $returnDate = null
        Log::info('Flight search initiated', [$any]);

        /**
         * array(9) {
         * [0]=>
         * string(3) "nbo"
         * [1]=>
         * string(3) "dxb"
         * [2]=>
         * string(6) "return"
         * [3]=>
         * string(1) "w"
         * [4]=>
         * string(10) "2022-12-09"
         * [5]=>
         * string(10) "2022-12-13"
         * [6]=>
         * string(1) "2"
         * [7]=>
         * string(1) "1"
         * [8]=>
         * string(1) "0"
         * }
         */

        $searchParams = explode('/', $any);
        $count = count($searchParams);
        $search = new FlightsSearchModel();


        //Check the number of parameters
        if ($count == 9) {
            //Return flight
            $search->origin = strtoupper($searchParams['0']);
            $search->origin_name =  $this->flightsModel->getAirportName(($searchParams['0']));
            $search->origin_city =  $this->flightsModel->getAirportCity(($searchParams['0']));
            $search->destination = strtoupper($searchParams['1']);
            $search->destination_name = $this->flightsModel->getAirportName(($searchParams['1']));
            $search->destination_city = $this->flightsModel->getAirportCity(($searchParams['1']));
            $search->tripType = $searchParams['2'];
            $search->classType = $searchParams['3'];
            $search->departureDate = $searchParams['4'];
            $search->returnDate = $searchParams['5'];
            $search->adults = $searchParams['6'];
            $search->children = $searchParams['7'];
            $search->infants = $searchParams['8'];

        } else {
            $search->origin = strtoupper($searchParams['0']);
            $search->origin_name =  $this->flightsModel->getAirportName(($searchParams['0']));
            $search->origin_city =  $this->flightsModel->getAirportCity(($searchParams['0']));
            $search->destination = strtoupper($searchParams['1']);
            $search->destination_name =  $this->flightsModel->getAirportName(($searchParams['1']));
            $search->destination_city =  $this->flightsModel->getAirportCity(($searchParams['1']));
            $search->tripType = $searchParams['2'];
            $search->classType = $searchParams['3'];
            $search->departureDate = $searchParams['4'];
            $search->returnDate = date('Y-m-d', strtotime($searchParams['4'] . " +30 days"));
            $search->adults = $searchParams['5'];
            $search->children = $searchParams['6'];
            $search->infants = $searchParams['7'];
        }

        $search->limit = 100;
        $search->broswer_version = $_SERVER['HTTP_USER_AGENT'];


        //Update search Model
        Session::put('flights_search_session', serialize($search));

        // Mapping travel class letters to full names


        // Translate travel class code to full name
        $fullTravelClass = $this->flightsModel->travelClass($search->classType);

        // Makes GET request to the Amadeus endpoint shopping/flight-offers
        try {
            $accessToken = $this->getAccessToken(); // Retrieving access token

            $queryParams = [
                'originLocationCode' => $search->origin,
                'destinationLocationCode' => $search->destination,
                'departureDate' => $search->departureDate,
                'adults' => $search->adults,
                'children' => $search->children,
                'infants' => $search->infants,
                'travelClass' => $fullTravelClass,
            ];

            if ($search->tripType == 'retkjurn') {
                $queryParams['returnDate'] = $search->returnDate;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl . 'v2/shopping/flight-offers', $queryParams);

            Log::info('Amadeus API response', $response->json());

            $data = $response->json();

            return response()->json($data);
        } catch (ConnectionException $e) {
            Log::error('Connection error: ' . $e->getMessage());
            return response()->json(['error' => 'Connection error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Error during flight search: ' . $e->getMessage());
            return response()->json(['error' => 'Error during flight search: ' . $e->getMessage()], 500);
        }
    }




//    public function search(Request $request): JsonResponse
//    {
//        Log::info('Flight search initiated', $request->all());
//
//        // Parameters retrieved from incoming HTTP requests
//        $originLocationCode = $request->input('origin');
//        $destinationLocationCode = $request->input('destination');
//        $departureDate = $request->input('departureDate');
//        $returnDate = $request->input('returnDate');
//        $adults = $request->input('adults', 1);
//        $children = $request->input('children', 0);
//        $infants = $request->input('infants', 0);
//        $travelClass = $request->input('travelClass');
////        $currency = $request->input('currency', 'USD');
//
//        // Makes GET request to the Amadeus endpoint shopping/flight-offers
//        try {
//            $accessToken = $this->getAccessToken(); // Retrieving access token
//
//            $response = Http::withHeaders([
//                'Authorization' => 'Bearer ' . $accessToken,
//            ])->get($this->amadeusApiUrl . 'v2/shopping/flight-offers', [
//                'originLocationCode' => $originLocationCode,
//                'destinationLocationCode' => $destinationLocationCode,
//                'departureDate' => $departureDate,
//                'returnDate' => $returnDate,
//                'adults' => $adults,
//                'children' => $children,
//                'infants' => $infants,
//                'travelClass' => $travelClass,
////                'currencyCode' => $currency,
//            ]);
//
//            Log::info('Amadeus API response', $response->json());
//
//            $data = $response->json();
//
//            return response()->json($data);
//        } catch (ConnectionException $e) {
//            Log::error('Connection error: ' . $e->getMessage());
//            return response()->json(['error' => 'Connection error: ' . $e->getMessage()], 500);
//        } catch (\Exception $e) {
//            Log::error('Error during flight search: ' . $e->getMessage());
//            return response()->json(['error' => 'Error during flight search: ' . $e->getMessage()], 500);
//        }
//    }

    public function confirmFlightOfferPrice($offer): JsonResponse
    {
        $flightOffer = json_decode($offer, true);

        // Makes POST request to the Amadeus endpoint shopping/flight-offers/pricing
        try {
            $accessToken = $this->getAccessToken(); // Retrieving access token

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($this->amadeusApiUrl . 'v1/shopping/flight-offers/pricing?include=credit-card-fees,bags,other-services,detailed-fare-rules&forceClass=false',
                $flightOffer);

            Log::info('Amadeus API response', $response->json());

            $data = $response->json();

            return response()->json($data);
        } catch (ConnectionException $e) {
            Log::error('Connection error: ' . $e->getMessage());
            return response()->json(['error' => 'Connection error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Error during flight search: ' . $e->getMessage());
            return response()->json(['error' => 'Error during flight search: ' . $e->getMessage()], 500);
        }
    }

    public function offers(Request $request): JsonResponse
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->amadeusApiUrl . 'shopping/flight-offers', [
                'originLocationCode' => $request->input('origin'),
                'destinationLocationCode' => $request->input('destination'),
                'departureDate' => $request->input('departureDate'),
                'returnDate' => $request->input('returnDate'),
                'adults' => $request->input('adults', 1),
                'children' => $request->input('children', 0),
                'infants' => $request->input('infants', 0),
                'currencyCode' => $request->input('currency', 'USD'),
            ]);

            $flightOffers = $response->json()['data'];

            // Determine sorting criteria based on user input (query parameters)
            $sortBy = $request->input('sort_by', 'price.total');
            $sortDirection = $request->input('sort_direction', 'asc');

            // Define sorting functions for different criteria
            $sortFunctions = [
                'price.total' => fn($offer) => $offer['price']['total'],
                'duration' => fn($offer) => strtotime($offer['itineraries'][0]['segments'][0]['arrival']['at']) - strtotime($offer['itineraries'][0]['segments'][0]['departure']['at']),
                'departureTime' => fn($offer) => strtotime($offer['itineraries'][0]['segments'][0]['departure']['at']),
                'arrivalTime' => fn($offer) => strtotime($offer['itineraries'][0]['segments'][0]['arrival']['at']),
                'numberOfStops' => fn($offer) => count($offer['itineraries'][0]['segments']),
                'airline' => fn($offer) => $offer['itineraries'][0]['segments'][0]['carrierCode'],
                'class' => fn($offer) => $offer['travelerPricings'][0]['fareDetailsBySegment'][0]['cabin']
            ];

            // Perform sorting
            if (isset($sortFunctions[$sortBy])) {
                $sortedFlightOffers = collect($flightOffers)->sortBy($sortFunctions[$sortBy], SORT_NATURAL, $sortDirection === 'desc')->values()->all();
            } else {
                $sortedFlightOffers = $flightOffers; //no sorting if invalid sortBy value
            }

            return response()->json($sortedFlightOffers);
        } catch (ConnectionException $e) {
            return response()->json(['error' => 'Connection error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error during flight offers search: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */

    public function flightOffersPrice(Request $request): JsonResponse
    {
        try {
            // Decode JSON payload sent in the request body
            $flightOfferData = $request->all();

            // Ensure 'offer' key is present and decode it from base64
            if (!isset($flightOfferData['offer'])) {
                throw new \Exception('Missing offer data');
            }

            $base64Offer = $flightOfferData['offer'];
            $decodedOffer = json_decode(base64_decode($base64Offer), true);

            // Create a unique cache key based on the flight offer data
            $cacheKey = 'flight_offer_price_' . md5($base64Offer);

            // Check if the pricing data is already cached
            $cachedPriceData = Cache::get($cacheKey);

            if ($cachedPriceData) {
                // Return cached data
                return response()->json($cachedPriceData);
            }

            // Prepare data structure expected by Amadeus API
            $flightOffers = [
                'data' => [
                    'type' => 'flight-offers-pricing',
                    'flightOffers' => [$decodedOffer],
                ]
            ];

            // Get access token
            $accessToken = $this->getAccessToken();

            // Make POST request to Amadeus pricing endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->amadeusApiUrl . 'v1/shopping/flight-offers/pricing?include=credit-card-fees,bags,other-services,detailed-fare-rules', $flightOffers);


            // Log response from Amadeus API
            Log::info('Pricing API Response: ', $response->json());

            // Handle response from Amadeus API
            if ($response->successful() && isset($response->json()['data'])) {
                return response()->json($response->json()['data']);
            } else {
                return response()->json(['error' => 'Flight offers pricing data not found in response'], 500);
            }
        } catch (ConnectionException $e) {
            Log::error('Connection error: ' . $e->getMessage());
            return response()->json(['error' => 'Connection error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Error fetching flight offers price: ' . $e->getMessage());
            return response()->json(['error' => 'Error fetching flight offers price: ' . $e->getMessage()], 500);
        }
    }


    public function selectFlightOffer(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'flightOfferId' => 'required|string',
                'type' => 'required|string',
                'id' => 'required|string',
                'source' => 'required|string',
                'instantTicketingRequired' => 'required|boolean',
                'nonHomogeneous' => 'required|boolean',
                'oneWay' => 'required|boolean',
                'isUpsellOffer' => 'required|boolean',
                'lastTicketingDate' => 'required|date',
                'lastTicketingDateTime' => 'required|date',
                'numberOfBookableSeats' => 'required|integer',
                'itineraries' => 'required|array',
                'price' => 'required|array',
                'pricingOptions' => 'required|array',
                'validatingAirlineCodes' => 'required|array',
                'travelerPricings' => 'required|array',
            ]);
            //Storing the selected flight offer details in session
            session(['selectedFlightOfferId' => $data]);
//            session(['selectedFlightOffer' => $request->input('flightOfferId')]);
            return response()->json(['message' => 'Flight offer selected successfully', 'selectedFlightOfferId' => $data]);
        } catch (ConnectionException $e) {
            return response()->json(['error' => 'Connection error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error during flight selection: ' . $e->getMessage()], 500);
        }
    }


    // Access token required for authentication with the Amadeus API
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
}

