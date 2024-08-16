<?php

namespace App\Models;

use App\Http\Controllers\Flights\PriceSummaryController;
use App\Mail\TicketMail;
use App\Services\TicketService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\FlightOrders;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;


class Flight extends Model
{
    use HasFactory;

    protected $amadeusApiKey;
    protected $amadeusApiSecret;
    protected $amadeusApiUrl = 'https://test.api.amadeus.com/';

    protected $httpClient;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->amadeusApiKey = env('AMADEUS_API_KEY');
        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
//        $this->httpClient = new Client([
//            'base_uri' => $this->amadeusApiUrl,
//            'headers' => [
//                'Authorization' => 'Bearer ' . $this->getAccessToken(),
//            ],
//        ]);
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


    public function getAirportCity($query)
    {
        //$sql =  DB::table('flights_airports');
        $sql = Airport::where('code', $query)->first();
        //$sql->orderByRaw("(CASE WHEN ('code' LIKE '%$query%' AND 'cityName' LIKE '%$query%') THEN 1 WHEN ('code' LIKE '%$query%' AND 'cityName' NOT LIKE '%$query%') THEN 2 ELSE 3 END)");
        return $sql != null ? $sql->city : '';

    }

    public function getAirportName($query)
    {
        //$sql =  DB::table('flights_airports');
        $sql = Airport::where('code', $query)->first();
        //$sql->orderByRaw("(CASE WHEN ('code' LIKE '%$query%' AND 'cityName' LIKE '%$query%') THEN 1 WHEN ('code' LIKE '%$query%' AND 'cityName' NOT LIKE '%$query%') THEN 2 ELSE 3 END)");
        return $sql != null ? $sql->airport : '';

    }

    public function travelClass($classID)
    {
        $classID = strtoupper($classID);
        $travelClassMap = [
            'E' => 'ECONOMY',
            'B' => 'BUSINESS',
            'P' => 'PREMIUM_ECONOMY',
            'F' => 'FIRST'
        ];
        if (array_key_exists($classID, $travelClassMap)) {
            return $travelClassMap[$classID];
        } else {
            return $travelClassMap['E'];
        }
    }



    public function createOrder($bookingData, $bookingId): JsonResponse
    {
        try {
            $accessToken = $this->getAccessToken();

            $flightOffers = $bookingData['data']['flightOffers'] ?? null;

            if (empty($flightOffers)) {
                return response()->json(['error' => 'No flight offers provided.'], 400);
            }

            Log::info('Flight Offers:', ['flightOffers' => $flightOffers]);

            //order creation request payload
            $orderPayload = [
                'data' => [
                    'type' => 'flight-order',
                    'flightOffers' => $bookingData['data']['flightOffers'],
                    'travelers' => $bookingData['data']['travelers'],
                    'remarks' => $bookingData['data']['remarks'],
                    'ticketingAgreement' => $bookingData['data']['ticketingAgreement'],
                    'contacts' => $bookingData['data']['contacts'],
                ]
            ];

            Log::info('Order Payload:', ['orderPayload' => json_encode($orderPayload)]);

            //creating the flight order
            $orderResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->amadeusApiUrl . 'v1/booking/flight-orders', $orderPayload);

            if ($orderResponse->failed()) {
                Log::error('Failed to create flight order', ['response' => $orderResponse->body()]);
                return response()->json(['error' => 'Failed to create flight order'], 400);
            }

            $orderConfirmation = $orderResponse->json();
            Log::info('Order Confirmation:', ['orderConfirmation' => $orderConfirmation]);

            //storing order confirmation in db
            $booking = Booking::find($bookingId);

            if ($booking) {
                $booking->booking_info = json_encode($orderConfirmation); //stored as JSON
                $booking->status = Booking::STATUS_CONFIRMED; //update status to confirmed

                $booking->save();


                FlightOrders::create([
                    'booking_id' => $bookingId,
                    'type' => $orderConfirmation['data']['type'],
                    'order_id' => $orderConfirmation['data']['id'],
                    'queuing_office_id' => $orderConfirmation['data']['queuingOfficeId'] ?? null,
                    'reference' => $orderConfirmation['data']['associatedRecords'][0]['reference'] ?? null,
                    'creation_date' => $orderConfirmation['data']['associatedRecords'][0]['creationDate'] ?? null,
                    'origin_system_code' => $orderConfirmation['data']['associatedRecords'][0]['originSystemCode'] ?? null,
                    'flight_offer_id' => $orderConfirmation['data']['associatedRecords'][0]['flightOfferId'] ?? null,
                    'source' => $orderConfirmation['data']['flightOffers'][0]['source'] ?? null,
                    'non_homogeneous' => $orderConfirmation['data']['flightOffers'][0]['nonHomogeneous'] ?? false,
                    'last_ticketing_date' => $orderConfirmation['data']['flightOffers'][0]['lastTicketingDate'] ?? null,
                    'currency' => $orderConfirmation['data']['flightOffers'][0]['price']['currency'] ?? null,
                    'total' => $orderConfirmation['data']['flightOffers'][0]['price']['total'] ?? null,
                    'base' => $orderConfirmation['data']['flightOffers'][0]['price']['base'] ?? null,
                    'grand_total' => $orderConfirmation['data']['flightOffers'][0]['price']['grandTotal'] ?? null,
                    'billing_currency' => $orderConfirmation['data']['flightOffers'][0]['price']['billingCurrency'] ?? null,
                    'fare_type' => $orderConfirmation['data']['flightOffers'][0]['pricingOptions']['fareType'][0] ?? null,
                    'included_checked_bags_only' => $orderConfirmation['data']['flightOffers'][0]['pricingOptions']['includedCheckedBagsOnly'] ?? false,
                    'itineraries' => json_encode($orderConfirmation['data']['flightOffers'][0]['itineraries'] ?? []),
//                    'traveler_pricings' => json_encode($orderConfirmation['data']['flightOffers'][0]['travelerPricings'] ?? []),
                    'traveler_pricings' => json_encode($orderConfirmation['data']['flightOffers'][0]['travelerPricings'] ?? []),
                    'travelers' => json_encode($orderConfirmation['data']['travelers'] ?? []),
                    'remarks' => json_encode($orderConfirmation['data']['remarks'] ?? []),
                    'ticketing_agreement' => json_encode($orderConfirmation['data']['ticketingAgreement'] ?? []),
                    'automated_process' => json_encode($orderConfirmation['data']['automatedProcess'] ?? []),
                    'contacts' => json_encode($orderConfirmation['data']['contacts'] ?? []),
//                    'dictionaries' => json_encode($orderConfirmation['data']['dictionaries'] ?? []),
                    'dictionaries' => json_encode($orderConfirmation['dictionaries'] ?? []),
                ]);

                //calculate price summary
                $priceSummaryController = new PriceSummaryController();
                $priceSummary = $priceSummaryController->priceSummary($bookingId);

                //sending e-ticket
//                (new TicketMail($bookingId, new TicketService()))->sendTicketEmail($bookingId);
//                TicketMail::sendTicketEmail($bookingId, new TicketService());
//                TicketMail::sendTicketEmail($bookingId, $orderConfirmation['data']['id']);


                return response()->json([
//                    'message' => 'Flight order created and ticket sent successfully',
                    'message' => 'Flight order created successfully',
                    'order' => $orderConfirmation
                ], 200);
            } else {
                return response()->json(['error' => 'Booking not found.'], 404);
            }
        } catch (ConnectionException $e) {
            return response()->json(['status' => false, 'message' => 'Connection error' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error creating a flight order' . $e->getMessage()], 500);
        }
    }

}
