<?php

namespace App\Http\Controllers\Flights;
use App\Http\Controllers\Controller;
use App\Mail\TicketMail;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class PriceSummaryController extends Controller
{
    public function priceSummary($bookingId)
    {
        try {
            //retrieve the booking details from the database
            $booking = Booking::findOrFail($bookingId);
            //decoding JSON column
            $bookingInfo = json_decode($booking->booking_info, true);

            $summary = [//sets up an initial summary with all values being 0
                'base_price' => 0,
                'total_price' => 0,
                'fees' => [
                    'supplier' => 0,
                    'payment' => 0,
                    'ticketing' => 0,
                ],
                'additional_services' => [
                    'checked_bags' => 0,
                    'seats' => 0,
                    'insurance' => 0,
                ],
                'total_taxes' => 0,
            ];

            // Extract price details
            if (isset($bookingInfo['flightOffers'][0]['price'])) {
                $price = $bookingInfo['flightOffers'][0]['price'];
                $summary['base_price'] = (float)$price['base'];
                $summary['total_price'] = (float)$price['grandTotal'];

                // Fees
                foreach ($price['fees'] as $fee) {
                    if ($fee['type'] === 'SUPPLIER') {
                        $summary['fees']['supplier'] = (float)$fee['amount'];
                    } elseif ($fee['type'] === 'FORM_OF_PAYMENT') {
                        $summary['fees']['payment'] = (float)$fee['amount'];
                    } elseif ($fee['type'] === 'TICKETING') {
                        $summary['fees']['ticketing'] = (float)$fee['amount'];
                    }
                }
                //float is used to ensure values such as prices, fees, and amounts are handled as
                // numbers with decimal places

                // Additional Services
                foreach ($price['additionalServices'] as $service) {
                    if ($service['type'] === 'CHECKED_BAGS') {
                        $summary['additional_services']['checked_bags'] = (float)$service['amount'];
                    } elseif ($service['type'] === 'SEATS') {
                        $summary['additional_services']['seats'] = (float)$service['amount'];
                    } elseif ($service['type'] === 'INSURANCE') {
                        $summary['additional_services']['insurance'] = (float)$service['amount'];
                    }
                }

                // Taxes
                if (isset($price['total'])) {
                    $summary['total_taxes'] = (float)$price['total']
                        - $summary['base_price']
                        - $summary['fees']['supplier']
                        - $summary['fees']['payment']
                        - $summary['fees']['ticketing']
                        - $summary['additional_services']['checked_bags']
                        - $summary['additional_services']['seats']
                        - $summary['additional_services']['insurance'];
                }
            }
            $booking->save();

            return [
                'summary' => $summary,
                'total_price' => $summary['total_price']
            ];

        } catch (ModelNotFoundException $e) {
            Log::error('Booking not found: ' . $e->getMessage());
            return response()->json(['error' => 'Booking not found'], 404);

        } catch (\Exception $e) {
            Log::error('Error calculating price summary: ' . $e->getMessage());
            return response()->json(['error' => 'Error calculating price summary'], 500);
        }
    }

    function sendTicket($bookingId): JsonResponse
    {
        try {
            TicketMail::sendTicketEmail($bookingId);
            return response()->json(['message' => 'Ticket email sent successfully.']);
        } catch (Exception $e) {
            Log::error('Error sending ticket email: ' . $e->getMessage());
            return response()->json(['error' => 'Error sending ticket email'], 500);
        }
    }
}
