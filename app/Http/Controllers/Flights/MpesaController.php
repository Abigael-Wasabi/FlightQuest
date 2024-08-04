<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use SmoDav\Mpesa\Laravel\Facades\STK;
use Illuminate\Support\Facades\Auth;

class MpesaController extends Controller
{
    public function stkPush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric',
            'booking_id' => 'required|integer',
        ]);

        Log::info('Validated data: ', $validated);

        //format phone no
        $phone = $this->formatPhoneNumber($validated['phone']);
        $amount = (int) $validated['amount'];
        $booking_id = $validated['booking_id'];

        //booking id exists n is associated with the current user
        $booking = Booking::where('id', $booking_id)->where('user_id', Auth::id())->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found or not associated with user'], 404);
        }

        try {
            $response = STK::request($amount)
                ->from($phone)
                ->usingReference('Booking', $booking_id)
                ->push();

            Log::info('STK response: ', (array)$response);

            // Save the payment record
            Payments::create([
                'booking_id' => $booking_id,
                'MerchantRequestID' => $response->MerchantRequestID,
                'CheckoutRequestID' => $response->CheckoutRequestID,
                'ResponseCode' => $response->ResponseCode,
                'ResponseDescription' => $response->ResponseDescription,
                'CustomerMessage' => $response->CustomerMessage,
                'amount' => $amount,
                'phone' => $phone,
            ]);

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to initiate STK Push'. $e->getMessage()], 500);
        }
    }


    public function initiatePayment($phone, $amount)
    {
        $client = new Client();

        //generate access token
        $response = $client->request('POST', 'https://sandbox.safaricom.co.ke/oauth/v1/generate', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(env('MPESA_CONSUMER_KEY') . ':' . env('MPESA_CONSUMER_SECRET')),
                'Content-Type' => 'application/json',
            ],
        ]);

        $access_token = json_decode((string) $response->getBody())->access_token;

        // Initiate payment
        $response = $client->request('POST', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'BusinessShortCode' => env('MPESA_SHORTCODE'),
                'Password' => base64_encode(env('MPESA_SHORTCODE') . env('MPESA_PASSKEY') . date('YmdHis')),
                'Timestamp' => date('YmdHis'),
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phone,
                'PartyB' => env('MPESA_SHORTCODE'),
                'PhoneNumber' => $phone,
                'CallBackURL' => env('MPESA_CALLBACK_URL'),
                'AccountReference' => 'Payment',
                'TransactionDesc' => 'Payment for flight booking',
            ],
        ]);
        return $response->getBody();
    }

    public function callback(Request $request): JsonResponse
    {
        //Log the callback response for debugging
        Log::info('M-Pesa Callback:', $request->all());

        //validating and extract the callback data
        $callbackData = $request->get('Body')['stkCallback'];

        $checkoutRequestId = $callbackData['CheckoutRequestID'];
        $resultCode = $callbackData['ResultCode'];
        $resultDesc = $callbackData['ResultDesc'];

        //finding payment record using CheckoutRequestID
        $payment = Payments::where('CheckoutRequestID', $checkoutRequestId)->first();

        if ($payment) {
            // Update the payment record with the callback data
            $payment->update([
                'ResultCode' => $resultCode,
                'ResultDesc' => $resultDesc,
                'Amount' => $callbackData['CallbackMetadata']['Item'][0]['Value'] ?? null,
                'MpesaReceiptNumber' => $callbackData['CallbackMetadata']['Item'][1]['Value'] ?? null,
                'Balance' => $callbackData['CallbackMetadata']['Item'][2]['Value'] ?? null,
                'TransactionDate' => $callbackData['CallbackMetadata']['Item'][3]['Value'] ?? null,
                'PhoneNumber' => $callbackData['CallbackMetadata']['Item'][4]['Value'] ?? null
            ]);

            // If the transaction was successful,update the booking status
            $booking = $payment->booking_id;
            if ($resultCode == 0) {
                $booking->update(['status' => Booking::STATUS_COMPLETED]);
            }
            else {
                $booking->update(['status' => Booking::STATUS_FAILED]);
            }
        }
        return response()->json(['status' => 'success'], 200);
    }


    //formating phone number to a valid Kenyan number format.
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone); //removes non-numeric characters
        if (substr($phone, 0, 3) == '254') {
            $phone = '0' . substr($phone, 3);
        } elseif (substr($phone, 0, 4) == '+254') {
            $phone = '0' . substr($phone, 4);
        }

        // Ensure the phone number is 10 digits long
        if (strlen($phone) != 10) {
            throw new \Exception('Invalid phone number format');
        }

        return $phone;
    }
}
