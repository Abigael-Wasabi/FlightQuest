<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\request;
use Illuminate\Support\Facades\Log;

class FlightOrderController extends Controller
{
    protected mixed $amadeusApiKey;
    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public function __construct()
    {
        $this->amadeusApiKey = env('AMADEUS_API_KEY');
        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
    }



    public function retrieve($flightOrderId, Request $request): JsonResponse
    {
        try {
            $reference = $request->input('reference');
            $accessToken = $this->getAccessToken();

            Log::info('Flight order ID:', ['flightOrderId' => $flightOrderId]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->amadeusApiUrl . 'v1/booking/flight-orders/' . $flightOrderId, [
                'reference' => $reference,
            ]);

            Log::info('Amadeus API retrieve order response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                return response()->json(['error' => 'Failed to retrieve flight order.'], $response->status());
            }
        } catch (Exception $e) {
            Log::error('Error retrieving flight order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve flight order.'], 500);
        }
    }

    public function cancel($flightOrderId): JsonResponse
    {
        try {
            $accessToken = $this->getAccessToken();

            Log::info('Flight order ID:', ['flightOrderId' => $flightOrderId]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->delete($this->amadeusApiUrl . 'v1/booking/flight-orders/' . $flightOrderId);

            Log::info('Amadeus API cancel order response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                //update status column to b canceled
                $booking = Booking::find($flightOrderId);
                if ($booking) {
                    $booking->status = Booking::STATUS_CANCELED;
                    $booking->save();
                }
                return response()->json(['message' => 'Flight order canceled successfully.']);
            } else {
                return response()->json(['error' => 'Failed to cancel flight order.'], $response->status());
            }
        } catch (Exception $e) {
            Log::error('Error canceling flight order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel flight order.'], 500);
        }
    }


    public function getSeatMap(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'flightOffers' => 'required|array',
            ]);
            Log::info($request);

            $flightOffers = $request->input('flightOffers');

            //retrieving access token
            $accessToken = $this->getAccessToken();

            //Making an API request to Amadeus
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($this->amadeusApiUrl . 'v1/shopping/seatmaps', [
                'data' => $flightOffers
            ]);

            //Logging the response for debugging
            Log::info('Amadeus Seatmap API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to retrieve seat map',
                    'details' => $response->json()
                ], $response->status());
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch seat map', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * @throws Exception
     */
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
                throw new Exception('Access token not found in response');
            }
        } catch (Exception $e) {
            Log::error('Error fetching access token: ' . $e->getMessage());
            throw new Exception('Error fetching access token: ' . $e->getMessage());
        }
    }
}
