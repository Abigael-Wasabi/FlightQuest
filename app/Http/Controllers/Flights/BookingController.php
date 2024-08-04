<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Passengers;
use App\Models\Seats;
use App\Models\Flight;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected mixed $amadeusApiKey;
    protected mixed $amadeusApiSecret;
    protected mixed $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public function __construct(){
        $this->amadeusApiKey = env('amadeus_api_key');
        $this->amadeusApiSecret = env('amadeus_api_secret');
    }

    public function store(Request $request): JsonResponse
    {

        try{
            Log::info('Store Passengers Details:', $request->all());
            // Validate the request
            $request->validate([
                'passengers' => 'required|array|min:1',
                'passengers.*.travelerType' => 'required|string|in:adult,child,infant',
                'passengers.*.travelerId' => 'required|string|max:255',
                'passengers.*.name.firstName' => 'required|string|max:255',
                'passengers.*.name.lastName' => 'required|string|max:255',
                'passengers.*.dateOfBirth' => 'required|date',
                'passengers.*.email' => 'required|email|max:255',
                'passengers.*.phone_number' => 'required|string|max:20',
                'passengers.*.passport_number' => 'required_if:passengers.*.travelerType,adult|string|max:20',
                'passengers.*.birth_certificate_number' => 'required_if:passengers.*.travelerType,child,infant|string|max:20',
                'passengers.*.nationality' => 'required|string',
                'luggage' => 'required|array',
                'seats' => 'required|array',
                'flightOffers' => 'required|string'
            ]);

            // Decode the flight offer
            $decodedOffer = base64_decode($request->input('flightOffers'));
            $offer = json_decode($decodedOffer, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Error decoding flight offer:', ['error' => json_last_error_msg()]);
                return response()->json(['message' => 'Invalid flight offer data'], 400);
            }

            //Create a new booking
            $booking = Booking::create([
                'offer_id' => $offer['id'],
                'flight_details' => json_encode($offer),
                'passenger_info' => json_encode($request->input('passengers')),
                'luggage_info' => json_encode($request->input('luggage')),
                'seats_info' => json_encode($request->input('seats'))
            ]);

            if (!$booking) {
                Log::error('Error creating booking:', ['offer' => $offer]);
                return response()->json(['message' => 'Failed to create booking'], 500);
            }

            // Save passengers
            foreach ($request->input('passengers') as $passenger) {
                Log::info('Passenger Email:', ['email' => $passenger['email']]);
                $passengerCreated = Passengers::create([
                    'booking_id' => $booking->id,
                    'travelerType' => $passenger['travelerType'],
                    'travelerId' => $passenger['travelerId'],
                    'firstName' => $passenger['name']['firstName'],
                    'lastName' => $passenger['name']['lastName'],
                    'dateOfBirth' => $passenger['dateOfBirth'],
                    'email' => $passenger['email'],
                    'phone_number' => $passenger['phone_number'],
                    'passport_number' => $passenger['passport_number'] ?? null,
                    'birth_certificate_number' => $passenger['birth_certificate_number'] ?? null,
                    'nationality' => $passenger['nationality']
                ]);

                Log::info('Passenger Data:', ['passenger' => $passenger]);

                if (!$passengerCreated) {
                    Log::error('Error saving passenger:', ['passenger' => $passenger]);
                    return response()->json(['message' => 'Failed to save passenger'], 500);
                }
            }

            // Save luggage
            foreach ($request->input('luggage') as $luggage) {
                $luggageCreated = Luggage::create([
                    'booking_id' => $booking->id,
                    'type' => $luggage['type'],
                    'weight_in_kg' => $luggage['weight_in_kg']
                ]);

                if (!$luggageCreated) {
                    Log::error('Error saving luggage:', ['luggage' => $luggage]);
                    return response()->json(['message' => 'Failed to save luggage'], 500);
                }
            }

            // Save seats
            foreach ($request->input('seats') as $seat) {
                $seatCreated = Seats::create([
                    'booking_id' => $booking->id,
                    'seat_number' => $seat['seat_number'],
                    'class' => $seat['class'],
                    'type' => $seat['type'] ?? null
                ]);

                if (!$seatCreated) {
                    Log::error('Error saving seat:', ['seat' => $seat]);
                    return response()->json(['message' => 'Failed to save seat'], 500);
                }
            }

            //preparing the booking data for the createOrder method
            $bookingData = [
                'data' => [
                    'type' => 'flight-order',
                    'flightOffers' => [$offer],
                    'travelers' => $request->input('passengers'),
                    'remarks' => [
                        'general' => [
                            [
                                'subType' => 'GENERAL_MISCELLANEOUS',
                                'text' => 'ONLINE BOOKING WITH OnlineJetLife'
                            ]
                        ]
                    ],
                    'ticketingAgreement' => [
                        'option'=> 'DELAY_TO_CANCEL',
                        'delay' => '6D'
                    ],
                    'contacts' => [
                        [
                            'addresseeName' => [
                                'firstName' => $request->input('passengers')[0]['name']['firstName'],
                                'lastName' => $request->input('passengers')[0]['name']['lastName']
                            ],
                            'companyName' => 'OnlineJetLife',
                            'purpose' => 'STANDARD',
                            'phones' => [
                                [
                                    'deviceType' => 'MOBILE',
                                    'countryCallingCode' => '254',
                                    'number' => $request->input('passengers')[0]['phone_number']
                                ]
                            ],
//                            'emailAddress' => $request->input('passengers')[0]['email'],
                            'address' => [
                                'lines' => ['123 Moi Avenue'],
                                'postalCode' => '12345',
                                'cityName' => 'Nairobi',
                                'countryCode' => 'KE'
                            ]
                        ]
                    ]
                ]
            ];

            //calling the createOrder method
            $flightModel = new Flight();
            Log::info('Calling createOrder function in Flight model', ['bookingData' => $bookingData, 'bookingId' => $booking->id]);
            $orderResponse = $flightModel->createOrder($bookingData, $booking->id);
            Log::info('createOrder response', ['order' => $orderResponse]);

            return $orderResponse;

        }catch (\Exception $exception){
            Log::error('Error creating booking:', ['exception' => $exception]);
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
