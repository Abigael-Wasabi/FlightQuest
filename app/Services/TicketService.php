<?php

namespace App\Services;

use App\Http\Controllers\Flights\PriceSummaryController;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Booking;
use App\Models\FlightOrders;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function generatePdf($bookingId, $flightOrderId)
    {
        $data = $this->getTicketData($bookingId, $flightOrderId);
        $pdf = Pdf::loadView('emails.tickets', $data);
        Log::info('PDF generated for booking', [
            'bookingId' => $bookingId,
            'data' => $data,
        ]);
        return $pdf->output();
    }

    public function getTicketData($bookingId, $flightOrderId)
    {
        // Retrieve the booking record by ID
        $booking = Booking::findOrFail($bookingId);
        //getting flight order record by ID
        $flight_order = FlightOrders::findOrFail($flightOrderId);

        // Decoding JSON to an array
        $passengerInfo = json_decode($booking->passenger_info, true);
        $flightDetails = json_decode($booking->flight_details, true);
        $seatsInfo = json_decode($booking->seats_info, true);
        $luggageInfo = json_decode($booking->luggage_info, true);
        $status = $booking->status;
        //getting order id,reference n queuingOfficeId
        $theFlightOrderId = ($flight_order->order_id);
        $queuingOfficeId = $flight_order->queuing_office_id;
        $reference = ($flight_order->reference);

        // Call the price summary method
        $priceSummaryController = new PriceSummaryController();
        $priceSummary = $priceSummaryController->priceSummary($bookingId);

        // Prepare data for the email
        $data = [
            'passengerInfo' => $passengerInfo, // All passengers' details
            'flightDetails' => $flightDetails,
            'seatsInfo' => $seatsInfo ?? [],
            'luggageInfo' => $luggageInfo ?? [],
            'status' => $status,
            'flightOrderId' => $theFlightOrderId,
            'queuingOfficeId' => $queuingOfficeId,
            'reference' => $reference,
            'booking_data' => $priceSummary['summary'],
        ];

        return $data;
    }
}
