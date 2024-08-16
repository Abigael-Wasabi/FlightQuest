<?php

namespace App\Http\Controllers\Cars;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use App\Services\AmadeusAuthService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

            $accessToken = AmadeusAuthService::getAccessToken(); // Retrieving access token

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
        try {
            // Validate the input//any is required
            $request->validate([
                'cityName' => 'required_without:cityCode|string',
                'cityCode' => 'required_without:cityName|string',
            ]);
            $accessToken = AmadeusAuthService::getAccessToken();
            //retrieving access token


            $cityName = $request->query('cityName');
            $cityCode = $request->query('cityCode');
//            $amenities = $request->query('amenities');

            //Retrieve the city code using the city name
            if (empty($cityCode) && !empty($cityName)) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->get($this->amadeusApiUrl . 'v1/reference-data/locations', [
                    'keyword' => $cityName,
                    'subType' => 'CITY',
                ]);

                if ($response->failed()) {
                    return response()->json([
                        'error' => 'Failed to retrieve city code',
                        'details' => $response->json(),
                    ], $response->status());
                }

                $cityData = $response->json();
                if (isset($cityData['data'][0]['iataCode'])) {
                    $cityCode = $cityData['data'][0]['iataCode'];
                } else {
                    return response()->json([
                        'error' => 'City code not found for the provided city name',
                    ], 404);
                }
            }

            // Now search for hotels using the city code
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/hotels/by-city', [
                'cityCode' => $cityCode,
            ]);

            Log::info('Amadeus Hotel Search by City Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to search hotels by city',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to search hotels by city', 'message' => $e->getMessage()], 500);
        }
    }



    //use the retrieved hotel ID's
//URI TOO LONG
    public function hotelOffers(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'hotelId' => 'required|string',
                'checkInDate' => 'required|date',
                'checkOutDate' => 'required|date',
                'adults' => 'required|integer|min:1',
                'roomQuantity' => 'integer|nullable',
                'radius' => 'integer|nullable',
                'radiusUnit' => 'string|nullable',
                'hotelName' => 'string|nullable',
                'priceRange' => 'string|nullable',
                'currency' => 'string|nullable',
                'paymentPolicy' => 'string|nullable',
                'boardType' => 'string|nullable',
                'includeClosed' => 'boolean|nullable',
                'bestRateOnly' => 'boolean|nullable',
                'view' => 'string|nullable',
            ]);

            $accessToken = AmadeusAuthService::getAccessToken();

            $hotelIds = array( $request->input('hotelId'));
//            $hotelIds = array_map(function ($hotel) {
//                return $hotel;
//            }, $hotelIdsData);

            if (empty($hotelIds)) {
                return response()->json([
                    'error' => 'No hotels found with that reference code',
                ], 404);
            }

            //splitting IDs into chunks to avoid exceeding URL length limits
            $hotelOffers = [];
            $chunkSize = 10; //chunk size
            foreach (array_chunk($hotelIds, $chunkSize) as $chunk) {
                $queryParams = $request->only([
                    'checkInDate', 'checkOutDate', 'adults', 'roomQuantity', 'radius',
                    'radiusUnit', 'hotelName', 'priceRange', 'currency', 'paymentPolicy',
                    'boardType', 'includeClosed', 'bestRateOnly', 'view'
                ]);

                $queryParams['hotelIds'] = implode(',', $chunk); // Include hotel IDs

            $offersResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v3/shopping/hotel-offers', $queryParams);

            Log::info('Amadeus Hotel Offers Response', [
                'status' => $offersResponse->status(),
                'body' => $offersResponse->body(),
            ]);

                if ($offersResponse->failed()) {
                    return response()->json([
                        'error' => 'Failed to retrieve hotel offers',
                        'details' => $offersResponse->json(),
                    ], $offersResponse->status());
                }

                $hotelOffers = array_merge($hotelOffers, $offersResponse->json()['data']);
            }
//            return response()->json($offersResponse->json());
            return response()->json(['data' => $hotelOffers]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to retrieve hotel offers', 'message' => $e->getMessage()], 500);
        }
    }


    public function hotelOfferById(Request $request, $offerId): JsonResponse
    {
        try {
            $accessToken = AmadeusAuthService::getAccessToken(); // Retrieving access token

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v3/shopping/hotel-offers/' . $offerId);

            Log::info('Amadeus Hotel Offer By ID Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve hotel offer details',
                    'details' => $response->json(),
                ], $response->status());
            }
            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Failed to retrieve hotel offer details', 'message' => $e->getMessage()], 500);
        }
    }



    public function bookHotel(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'hotelOfferId' => 'required|string',
                'guests' => 'required|array|min:1',
                'paymentMethod' => 'required|string',
                'cardVendorCode' => 'required|string',
                'cardNumber' => 'required|string',
                'expiryDate' => 'required|date_format:Y-m',
                'cardHolderName' => 'required|string',
            ]);
            Log::info('Request Data:', $request->all());
            $accessToken = AmadeusAuthService::getAccessToken();

            //booking details from the request
            $hotelOfferId = $request->input('hotelOfferId');
            $guests = $request->input('guests'); //Guests r an array of guest details

            if (empty($guests) || !isset($guests[0]['email'])) {
                throw new Exception('Guest data is missing or improperly formatted.');
            }
            // Log the booking attempt
            Log::info('Booking hotel offer ID:', ['hotelOfferId' => $hotelOfferId]);
            Log::info('Guests:', ['guests' => $guests]);

            //the booking payload
            $paymentMethod = $request->input('paymentMethod'); //e.g CREDIT_CARD
            $cardVendorCode = $request->input('cardVendorCode');
            $cardNumber = $request->input('cardNumber');
            $expiryDate = $request->input('expiryDate');
            $cardHolderName = $request->input('cardHolderName');

            //checking if payment details r present
            if (!$paymentMethod || !$cardVendorCode || !$cardNumber || !$expiryDate || !$cardHolderName) {
                throw new Exception('Payment details are missing.');
            }
            // Check for valid expiry date format
            if (!preg_match('/^\d{4}-\d{2}$/', $expiryDate)) {
                throw new Exception('Invalid expiry date format.');
            }

            $bookingData = [
                'data' => [
                    'type' => 'hotel-order',
                    'guests' => $guests,
                    'travelAgent' => [
                        'contact' => [
                            'email' => $guests[0]['email'], //the 1st guest's email
                        ]
                    ],
                    'roomAssociations' => [
                        [
                            'guestReferences' => [
                                [
                                    'guestReference' => "1"
                                ]
                            ],
                            'hotelOfferId' => $hotelOfferId
                        ]
                    ],
                    'payment' => [
                        'method' => $paymentMethod,
                        'paymentCard' => [
                            'paymentCardInfo' => [
                                'vendorCode' => $cardVendorCode,
                                'cardNumber' => $cardNumber,
                                'expiryDate' => $expiryDate,
                                'holderName' => $cardHolderName,
                            ]
                        ]
                    ]
                ]
            ];
            Log::info('Payload to Amadeus API', ['payload' => $bookingData]);

            //making a POST request to Amadeus API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->amadeusApiUrl . 'v2/booking/hotel-orders', $bookingData);

            //logging
            Log::info('Amadeus API hotel booking response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            //response based on success or failure
            if ($response->successful()) {
                $responseData = $response->json();

                $hotelBooking = $responseData['data']['hotelBookings'][0];//getting the first booking
                $hotelOffer = $hotelBooking['hotelOffer'];//extracting the hotel offer details
                $hotel = $hotelBooking['hotel'];//extracting the hotel details
                $room = $hotelOffer['room'];//extracting the room details

                // Create the HotelBooking record in the database
                HotelBooking::create([
                    'hotel_offer_id' => $hotelOffer['id'],
                    'booking_id' => $responseData['data']['id'],
                    'confirmation_number' => $hotelBooking['hotelProviderInformation'][0]['confirmationNumber'],
                    'hotel_id' => $hotel['hotelId'],
                    'hotel_name' => $hotel['name'],
                    'hotel_chain_code' => $hotel['chainCode'],
                    'room_type' => $room['type'],
                    'check_in_date' => $hotelOffer['checkInDate'],
                    'check_out_date' => $hotelOffer['checkOutDate'],
                    'room_quantity' => $hotelOffer['roomQuantity'],
                    'room_description' => $room['description'],
                    'base_price' => $hotelOffer['price']['base'],
                    'total_price' => $hotelOffer['price']['total'],
                    'currency' => $hotelOffer['price']['currency'],
                    'guests' => json_encode($responseData['data']['guests']),
                    'room_associations' => json_encode($hotelBooking['roomAssociations']),
                    'payment' => json_encode($hotelBooking['payment']),
                    'travel_agent_id' => $hotelBooking['travelAgentId'],
                    'associated_records' => json_encode($responseData['data']['associatedRecords']),
                ]);

                return response()->json([
                    'message' => 'Hotel booking was successful.',
                    'booking_details' => $responseData
                ]);
            } else {
                return response()->json(['error' => 'Failed to book hotel.'], $response->status());
            }
        } catch (Exception $e) {
            Log::error('Error booking hotel: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to book hotel.'], 500);
        }
    }


    //use the retrieved hotelIds to fetch ratings.
    public function getHotelRatings(Request $request): JsonResponse
    {
        try {
            $accessToken = AmadeusAuthService::getAccessToken();
            $hotelId = $request->query('hotelIds');//getting hotelIds from the query params

            Log::info('Hotel ID:', ['hotelId' => $hotelId]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v2/e-reputation/hotel-sentiments', [
                    'hotelIds' => $hotelId,
            ]);

            Log::info('Amadeus API retrieve hotel ratings response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Failed to retrieve hotel ratings.'], $response->status());
            }
        } catch (Exception $e) {
            Log::error('Error retrieving hotel ratings: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve hotel ratings.'], 500);
        }
    }


    public function hotelNameAutocomplete(Request $request): JsonResponse
    {
        try {
            $accessToken = AmadeusAuthService::getAccessToken();
            $keyword = $request->query('keyword'); //keyword to search for

            //default subType values
            $subType = ['HOTEL_LEISURE', 'HOTEL_GDS'];

            Log::info('Keyword for hotel name autocomplete:', ['keyword' => $keyword]);
            Log::info('SubType for hotel name autocomplete:', ['subType' => $subType]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v1/reference-data/locations/hotel', [
                'keyword' => $keyword,
                'subType' => $subType,
            ]);

            Log::info('Amadeus API Hotel Name Autocomplete Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Failed to retrieve hotel name suggestions.'], $response->status());
            }
        } catch (Exception $e) {
            Log::error('Error retrieving hotel name suggestions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve hotel name suggestions.'], 500);
        }
    }



}
