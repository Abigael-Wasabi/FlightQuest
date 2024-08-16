<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AmadeusAuthService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\request;
use Illuminate\Support\Facades\Log;

class FlightOrderController extends Controller
{
//    protected mixed $amadeusApiKey;
//    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public function __construct()
    {
//        $this->amadeusApiKey = env('AMADEUS_API_KEY');
//        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
    }

    public function retrieve(Request $request, $flightOrderId): JsonResponse
    {
        try {
            Log::info('Retrieve Flight Order Request', [
                'flightOrderId' => $flightOrderId,
//                'reference' => $reference,
            ]);

            $encodedFlightOrderId = rawurlencode($flightOrderId);

            $accessToken = AmadeusAuthService::getAccessToken();

            $apiUrl = "{$this->amadeusApiUrl}v1/booking/flight-orders/{$encodedFlightOrderId}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($apiUrl);

            Log::info('Amadeus Flight Order API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve flight order',
                    'details' => $response->json()
                ], $response->status());
            }
            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Error retrieving flight order', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to retrieve flight order', 'message' => $e->getMessage()], 500);
        }
    }



    public function cancel(Request $request, $flightOrderId): JsonResponse
    {
        try {
            Log::info('Cancel Flight Order Request', [
                'flightOrderId' => $flightOrderId,
            ]);

            $encodedFlightOrderId = rawurlencode($flightOrderId);

            $accessToken = AmadeusAuthService::getAccessToken();

            $apiUrl = "{$this->amadeusApiUrl}v1/booking/flight-orders/{$encodedFlightOrderId}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->delete($apiUrl);

            Log::info('Amadeus Flight Order API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                //finding booking by flightOrderId in booking_info column
                $booking = Booking::whereRaw('JSON_UNQUOTE(JSON_EXTRACT(booking_info, "$.data.id")) = ?', [$flightOrderId])->first();

                if ($booking) {
                    Log::info('Searching for booking with flightOrderId:', [
                        'flightOrderId' => $flightOrderId,
                    ]);
                    $booking->status = Booking::STATUS_CANCELED;
                    $booking->save();
                    return response()->json(['message' => 'Flight order successfully cancelled.']);
                } else {
                    return response()->json(['error' => 'Booking not found.'], 404);
                }
            } else {
                return response()->json([
                    'error' => 'Failed to cancel flight order.',
                    'details' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Error canceling flight order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel flight order.'], 500);
        }
    }



}
