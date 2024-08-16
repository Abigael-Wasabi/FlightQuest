<?php

namespace App\Http\Controllers\Cars;

use App\Http\Controllers\Controller;
use App\Models\Cities;
use App\Models\TransfersBooking;
use App\Services\AmadeusAuthService;
//use DB;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CarsTransfersController extends Controller
{
//    protected mixed $amadeusApiKey;
//    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public function __construct(){
//        $this->amadeusApiKey = env('amadeus_api_key');
//        $this->amadeusApiSecret = env('amadeus_api_secret');
    }

    public function transferSearch(Request $request): JsonResponse
    {
        try {
            // Validate user input
            $request->validate([
                'startCityName' => 'required|string',
                'endCityName' => 'required|string',
                'startDateTime' => 'required|date_format:Y-m-d\TH:i:s',
                'passengers' => 'nullable|integer|min:1',
                'transferType' => 'required|string|in:private,shared,PRIVATE,SHARED'
            ]);
            //default passengers value is 1 if not provided
            $passengers = $request->input('passengers', 1);
            //formatting the transferType to uppercase
            $transferType = strtoupper($request->input('transferType'));
            //formatted transferType is valid
            if (!in_array($transferType, ['PRIVATE', 'SHARED'])) {
                return response()->json(['error' => 'Invalid transferType value'], 400);
            }
            // Get the access token
            $accessToken = AmadeusAuthService::getAccessToken();
            //startLocationCode obtained frm startCityName provided
            $startLocationCode = $this->getAirportCode($accessToken, $request->input('startCityName'));
            //endCountryCode obtained frm endCityName provided
            $endCountryCode = $this->getCountryCode($accessToken, $request->input('endCityName'));
            //endGeoCode(lat n long)obtained frm endCityName provided
            $endGeoCode = $this->getGeoCode($accessToken, $request->input('endCityName'));

            //payload
            $payload = [
                'startLocationCode' => $startLocationCode,
                'endAddressLine' => $request->input('endCityName'),
                'endCityName' => $request->input('endCityName'),
                'endCountryCode' => $endCountryCode,
                'endName' => $request->input('endCityName'),
                'endGeoCode' => $endGeoCode,
                'transferType' => $transferType,//uppercase transferType
                'startDateTime' => $request->input('startDateTime'),
                'passengers' => $passengers,
            ];
            // Log the payload
            Log::info('Amadeus API Payload', $payload);

            $response = Http::retry(3, 100)->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->amadeusApiUrl . 'v1/shopping/transfer-offers', $payload);

            Log::info('Amadeus Transfer Search API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                $errors = $response->json('errors') ?? [];
                $userMessage = 'Some transfer services are unavailable';
                return response()->json([
                    'error' => 'Failed to search transfer offers',
                    'details' => $response->json(),
                    'message' => $userMessage,
                ], $response->status());
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to search transfer offers', 'message' => $e->getMessage()], 500);
        }
    }

//    public function bookingTransfer(Request $request): JsonResponse
//    {
//        try{
//            //Validating
//            $request->validate([
//                'offerId' => 'required|string',
//                'transferInfo' => 'required|array',
//                'paymentInfo' => 'required|array'
//            ]);
//            $offerId = $request->query('offerId');
//            $transferInfo = $request->input('transferInfo');
//            $paymentInfo = $request->input('paymentInfo');
//
//            Log::info('Booking Transfer Input', [
//                'offerId' => $offerId,
//                'transferInfo' => $transferInfo,
//                'paymentInfo' => $paymentInfo
//            ]);
//            // Checking if transferInfo and paymentInfo are properly set
//            if (!$transferInfo || !$paymentInfo) {
//                return response()->json(['error' => 'Missing transferInfo or paymentInfo'], 400);
//            }
//             $accessToken = AmadeusAuthService::getAccessToken();
//
//            $payload = [
//                'data' => [
//                    'note' => $transferInfo['note'] ?? '',
//                    'passengers' => $transferInfo['passengers'] ?? [],
//                    'payment' => $paymentInfo['payment'] ?? []
//                ],
//            ];
//            Log::info('Payload for Amadeus Transfer Booking API', ['payload' => $payload]);
//
//            $response = Http::withHeaders([
//                'Authorization' => 'Bearer ' . $accessToken,
//                'Content-Type' => 'application/json',
//            ])->post($this->amadeusApiUrl . 'v1/ordering/transfer-orders/' . $offerId, $payload);
//
//            Log::info('Amadeus Transfer Booking API Response', [
//                'status' => $response->status(),
//                'body' => $response->body(),
//            ]);
//
//            if ($response->failed()) {
//                return response()->json([
//                    'error' => 'Failed to book car transfer',
//                    'details' => $response->json(),
//                ], $response->status());
//            }
//            //Saving booking data to the local database
////            $bookingData = $response->json();
////            if (!isset($bookingData['data'])) {
////                Log::error('Unexpected response structure', ['response' => $bookingData]);
////                return response()->json(['error' => 'Unexpected response structure'], 500);
////            }
////            $transferBooking = new TransfersBooking();
////            $transferBooking->reference = $bookingData['data']['reference'];
////            $transferBooking->order_id = $bookingData['data']['id'];
////            $transferBooking->agency = $bookingData['data']['agency'] ?? [];
////            $transferBooking->transfers = $bookingData['data']['transfers'] ?? [];
////            $transferBooking->passengers = $bookingData['data']['passengers'] ?? [];
////            $transferBooking->passenger_characteristics = $bookingData['data']['passengerCharacteristics'] ?? [];
////            $transferBooking->extra_services = $bookingData['data']['extraServices'] ?? [];
////            $transferBooking->quotation = $bookingData['data']['quotation'] ?? [];
////            $transferBooking->converted = $bookingData['data']['converted'] ?? [];
////            $transferBooking->cancellation_rules = $bookingData['data']['cancellationRules'] ?? [];
////            $transferBooking->distance = $bookingData['data']['distance'] ?? [];
////
////            $transferBooking->save();
//
//            return response()->json($response->json());
//    } catch (Exception $e) {
//            Log::error('Error booking car transfer', ['exception' => $e]);
//            return response()->json(['error' => 'Failed to book car transfer', 'message' => $e->getMessage()], 500);
//        }
//    }
    public function bookingTransfer(Request $request): JsonResponse
    {
        try {
            // Validate essential inputs
            $request->validate([
                'offerId' => 'required|string',
                'passengerFirstName' => 'required|string',
                'passengerLastName' => 'required|string',
                'passengerTitle' => 'required|string|in:MR,MS,MRS,DR',
                'passengerPhoneNumber' => 'required|string',
                'passengerEmail' => 'required|email',
                'paymentMethod' => 'required|string|in:CREDIT_CARD',
                'creditCardNumber' => 'required|string',
                'creditCardHolderName' => 'required|string',
                'creditCardExpiryDate' => 'required|string',
                'creditCardCVV' => 'required|string',
            ]);

            $offerId = $request->query('offerId');

            // Prepare the payload with essential user input
            $payload = [
                'data' => [
                    'passengers' => [
                        [
                            'firstName' => $request->input('passengerFirstName'),
                            'lastName' => $request->input('passengerLastName'),
                            'title' => $request->input('passengerTitle'),
                            'contacts' => [
                                'phoneNumber' => $request->input('passengerPhoneNumber'),
                                'email' => $request->input('passengerEmail'),
                            ],
                            // Optionally add billing address if provided
                            'billingAddress' => $request->input('billingAddress') ?? [
                                    'line' => 'Default Street 123',
                                    'zip' => '00000',
                                    'countryCode' => 'US',
                                    'cityName' => 'Default City'
                                ]
                        ]
                    ],
                    'payment' => [
                        'methodOfPayment' => $request->input('paymentMethod'),
                        'creditCard' => [
                            'number' => $request->input('creditCardNumber'),
                            'holderName' => $request->input('creditCardHolderName'),
                            'vendorCode' => 'VI', // assuming Visa, change as needed
                            'expiryDate' => $request->input('creditCardExpiryDate'),
                            'cvv' => $request->input('creditCardCVV')
                        ]
                    ],
                    // Default note
                    'note' => $request->input('note') ?? 'No special instructions',
                ]
            ];

            $accessToken = AmadeusAuthService::getAccessToken();
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->amadeusApiUrl . 'v1/ordering/transfer-orders/' . $offerId, $payload);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to book car transfer',
                    'details' => $response->json(),
                ], $response->status());
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Error booking car transfer', ['exception' => $e]);
            return response()->json(['error' => 'Failed to book car transfer', 'message' => $e->getMessage()], 500);
        }
    }


    public function transferManagement(Request $request, $orderId): JsonResponse
    {
        try {
            $confirmNbr = $request->query('confirmNbr');

            if (!$confirmNbr) {
                return response()->json(['error' => 'Confirmation number is required'], 400);
            }
            Log::info('Cancelling transfer order:', [
                'orderId' => $orderId,
                'confirmNbr' => $confirmNbr,
            ]);
            $accessToken = AmadeusAuthService::getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->amadeusApiUrl.'v1/ordering/transfer-orders/' . $orderId . '/transfers/cancellation', [
                'confirmNbr' => $confirmNbr,
            ]);
            Log::info('Amadeus Transfer Management API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            //returning response as JSON
            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Failed to cancel car transfer order.'], $response->status());
            }
        } catch (Exception $e) {
            // Log the error message
            Log::error('Error canceling transfer order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel transfer order.'], 500);
        }
    }




    //helper func retrieves city code using Amadeus API
    private function getAirportCode($accessToken, $cityName)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->get($this->amadeusApiUrl . 'v1/reference-data/locations', [
            'keyword' => $cityName,
            'subType' => 'AIRPORT',
        ]);
        Log::info('Get Airport Code Response', ['response' => $response->json()]);
        $locations = $response->json('data');
        if (count($locations) > 0) {
            return $locations[0]['iataCode']; //returns the first matching airport code
        }
        return null; //if no airport is found
    }
    private function getCountryCode($accessToken, $cityName)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->get($this->amadeusApiUrl . 'v1/reference-data/locations', [
            'keyword' => $cityName,
            'subType' => 'CITY',
        ]);
        Log::info('Get Country Code Response', ['response' => $response->json()]);
        $locations = $response->json('data');
        if (count($locations) > 0) {
            return $locations[0]['address']['countryCode'];//returns the country code
        }
        return null;//if no city is found
    }
    private function getGeoCode($accessToken, $cityName)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->get($this->amadeusApiUrl . 'v1/reference-data/locations', [
            'keyword' => $cityName,
            'subType' => 'CITY',
        ]);
        Log::info('Get Geo Code Response', ['response' => $response->json()]);
        $locations = $response->json('data');
        if (count($locations) > 0 && isset($locations[0]['geoCode'])) {
            $geoCode = $locations[0]['geoCode'];
            return $geoCode['latitude'] . ',' . $geoCode['longitude'];
        }
        return null; //null if no geocode is found
    }
}
