<?php

namespace App\Http\Controllers\Cars;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CarsTransfersController extends Controller
{
    protected mixed $amadeusApiKey;
    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public function __construct(){
        $this->amadeusApiKey = env('amadeus_api_key');
        $this->amadeusApiSecret = env('amadeus_api_secret');
    }
//sample input
//{
//"startLocationCode": "CDG",
//"endAddressLine": "Avenue Anatole France, 5",
//"endCityName": "Paris",
//"endZipCode": "75007",
//"endCountryCode": "FR",
//"endName": "Souvenirs De La Tour",
//"endGeoCode": "48.859466,2.2976965",
//"transferType": "PRIVATE",
//"startDateTime": "2024-08-01T10:30:00",
//"passengers": 2,
//"stopOvers": [
//{
//"duration": "PT2H30M",
//"sequenceNumber": 1,
//"addressLine": "Avenue de la Bourdonnais, 19",
//"countryCode": "FR",
//"cityName": "Paris",
//"zipCode": "75007",
//"name": "De La Tours",
//"geoCode": "48.859477,2.2976985",
//"stateCode": "FR"
//}
//],
//"startConnectedSegment": {
//    "transportationType": "FLIGHT",
//    "transportationNumber": "AF380",
//    "departure": {
//        "localDateTime": "2024-08-01T09:00:00",
//      "iataCode": "NCE"
//    },
//    "arrival": {
//        "localDateTime": "2024-08-01T10:00:00",
//      "iataCode": "CDG"
//    }
//  },
//  "passengerCharacteristics": [
//    {
//        "passengerTypeCode": "ADT",
//      "age": 20
//    },
//    {
//        "passengerTypeCode": "CHD",
//      "age": 10
//    }
//  ]
//}

//    public function transferSearch(Request $request): JsonResponse
//    {
//        try{
//            $accessToken = $this->getAccessToken();
//
//            $response = Http::withHeaders([
//                'Authorization' => 'Bearer '.$accessToken,
//                'Content-Type' => 'application/json',
//            ])->post($this->amadeusApiUrl.'v1/shopping/transfer-offers', $request->json()->all());
//
//            Log::info('Amadeus Transfer Search API Response', [
//                'status' => $response->status(),
//                'body' => $response->body(),
//            ]);
//
//            if($response->failed()){
//                return response()->json([
//                    'error' => 'Failed to search transfer offers',
//                    'details' => $response->json(),
//                ], $response ->status());
//            }
//            return response()->json($response->json());
//        } catch(\Exception $e){
//            return response()->json(['error' => 'Failed to search transfer offers', 'message' => $e->getMessage()], 500);
//        }
//    }

//{
//"data": {
//"note": "Please be on time",
//"passengers": [
//{
//"firstName": "Abi",
//"lastName": "Wasabi",
//"title": "MRS",
//"contacts": {
//"phoneNumber": "+33123456789",
//"email": "abi@gmail.com"
//},
//"billingAddress": {
//    "line": "Avenue de la Bourdonnais, 19",
//          "zip": "75007",
//          "countryCode": "FR",
//          "cityName": "Paris"
//        }
//      }
//    ],
//    "agency": {
//    "contacts": [
//        {
//            "email": {
//            "address": "abc@test.com"
//          }
//        }
//      ]
//    },
//    "payment": {
//    "methodOfPayment": "CREDIT_CARD",
//      "creditCard": {
//        "number": "4111111111111111",
//        "holderName": "ABI WASABI",
//        "vendorCode": "VI",
//        "expiryDate": "1024",
//        "cvv": "123"
//      }
//    },
//    "extraServices": [
//      {
//          "code": "EWT",
//        "itemId": "EWT0291"
//      }
//    ],
//    "equipment": [
//      {
//          "code": "BBS"
//      }
//    ],
//    "corporation": {
//    "address": {
//        "line": "5 Avenue Anatole France",
//        "zip": "75007",
//        "countryCode": "FR",
//        "cityName": "Paris"
//      },
//      "info": {
//        "AU": "FHOWMD024",
//        "CE": "280421GH"
//      }
//    },
//    "startConnectedSegment": {
//    "transportationType": "FLIGHT",
//      "transportationNumber": "AF380",
//      "departure": {
//        "uicCode": "7400001",
//        "iataCode": "CDG",
//        "localDateTime": "2024-08-01T08:30:00"
//      },
//      "arrival": {
//        "uicCode": "7400001",
//        "iataCode": "CDG",
//        "localDateTime": "2024-08-01T10:30:00"
//      }
//    },
//    "endConnectedSegment": {
//    "transportationType": "FLIGHT",
//      "transportationNumber": "AF380",
//      "departure": {
//        "uicCode": "7400001",
//        "iataCode": "CDG",
//        "localDateTime": "2024-08-01T11:30:00"
//      },
//      "arrival": {
//        "uicCode": "7400001",
//        "iataCode": "CDG",
//        "localDateTime": "2024-08-01T13:30:00"
//      }
//    }
//  }
//}

    public function transferSearch(Request $request): JsonResponse
    {
        // Validate the user input
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'start_date_time' => 'required|date_format:Y-m-d\TH:i:', // Example: 2024-08-01T10:00:00
            'end_date_time' => 'required|date_format:Y-m-d\TH:i:',
            'max_results' => 'integer|nullable',
        ]);

        $origin = $request->input('origin');
        $destination = $request->input('destination');
        $startDateTime = $request->input('start_date_time');
        $endDateTime = $request->input('end_date_time');
        $maxResults = $request->input('max_results', 10); // Default to 10 if not provided

        try {
            // Get access token
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                return response()->json(['error' => 'Unable to authenticate with Amadeus API'], 500);
            }

            // Send request to Amadeus Transfer Search API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->get($this->amadeusApiUrl.'v1/shopping/transfer-offers', [
                'origin' => $origin,
                'destination' => $destination,
                'start_date_time' => $startDateTime,
                'end_date_time' => $endDateTime,
                'max_results' => $maxResults,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json($data, 200);
            }else {
                Log::error('Amadeus API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['error' => 'Failed to search transfer offers'], 500);
            }
        } catch (\Exception $e) {
            //Handling any exceptions
            Log::error('Exception caught while searching for transfers: ' . $e->getMessage(), ['exception' => $e,]);
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }



    public function bookingTransfer(Request $request): JsonResponse
    {
        try{
            $transferId  = $request->query('offerId');//getting the offerId from query string
            $travelerInfo = $request->input('transferInfo');
            $paymentInfo = $request->input('paymentInfo');

            $accessToken = $this->getAccessToken();

            $url = $this->amadeusApiUrl . 'v1/ordering/transfer-orders' . $transferId;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'data' => [
                    'note' => $travelerInfo['note'],
                    'passengers' => $travelerInfo['passengers'],
                    'agency' => $travelerInfo['agency'],
                    'payment' => $paymentInfo['payment'],
                    'extraServices' => $travelerInfo['extraServices'],
                    'equipment' => $travelerInfo['equipment'],
                    'corporation' => $travelerInfo['corporation'],
                    'startConnectedSegment' => $travelerInfo['startConnectedSegment'],
                    'endConnectedSegment' => $travelerInfo['endConnectedSegment']
                ],
            ]);

            Log::info('Amadeus Transfer Booking API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to book order',
                    'details' => $response->json(),
                ], $response ->status());
            }
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to book transfer', 'message' => $e->getMessage()], 500);
        }
    }

    public function transferManagement(Request $request, $orderId): JsonResponse
    {
        try {
            $confirmNbr = $request->query('confirmNbr');

            if (!$confirmNbr) {
                return response()->json(['error' => 'Confirmation number is required'], 400);
            }

            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->amadeusApiUrl.'v1/ordering/transfer-orders/' .$orderId. '/transfers/cancellation',[
                'confirmNbr' => $confirmNbr,
            ]);

            Log::info('Amadeus Transfer Management API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to cancel transfer',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to cancel transfer', 'message' => $e->getMessage()], 500);
        }
    }

    private function getAccessToken()
    {
        try {
            $response = Http::asForm()->post('https://test.api.amadeus.com/v1/security/oauth2/token/', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->amadeusApiKey,
                'client_secret' => $this->amadeusApiSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Amadeus API token response', $data);

                if (isset($data['access_token'])) {
                    return $data['access_token'];
                } else {
                    Log::error('Access token not found in response', $data);
                    throw new \Exception('Access token not found in response');
                }
            } else {
                Log::error('Error fetching access token, HTTP status: ' . $response->status(), [
                    'response' => $response->body(),
                ]);
                throw new \Exception('Error fetching access token, HTTP status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception caught while fetching access token: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw new \Exception('Exception caught while fetching access token: ' . $e->getMessage());
        }
    }
}
