<?php

namespace App\Services;

use App\Http\Controllers\Flights\PriceSummaryController;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function generatePdf($bookingId)
    {
        //retrieving the booking record by ID
        $booking = Booking::findOrFail($bookingId);
        //decoding the passenger infoJSON to an array
        $bookingDetails = json_decode($booking->passenger_info, true);
        Log::info('passenger details', $bookingDetails);

        //checking if the 'email' key exists in the first passenger's details
        if (isset($bookingDetails[0]['email'])) {
            $email = $bookingDetails[0]['email']; // Ensure accessing the first passenger's email
        } else {
            throw new \Exception('Passenger email is missing from booking details.');
        }

        //Creating an instance of PriceSummaryController n calling the priceSummary method
        $priceSummaryController = new PriceSummaryController();
        $priceSummary = $priceSummaryController->priceSummary($bookingId);

        $data = [
            'passenger' => $bookingDetails, //sll passengers' details
            'flight' => $booking->flight_details,
            'booking' => [
                'reference' => $booking->id,
                'status' => $booking->status,
            ],
            'itinerary' => $booking->flight_details['itinerary'] ?? null, //null if not set
            'services' =>  $booking->additional_services ?? [], //empty array if not set
            'price_summary' => $priceSummary['summary'],
        ];

        //generating PDF using the view 'emails.tickets' and the data
        $pdf = Pdf::loadView('emails.tickets', $data);
        //logging the details of the generated PDF
        Log::info('PDF generated for booking', [
            'bookingId' => $bookingId,
            'data' => $data,
        ]);

        //returns the generated PDF as output
        return $pdf->output();
    }
}
