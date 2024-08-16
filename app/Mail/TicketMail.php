<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Services\TicketService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $bookingId;
    protected $ticketService;
    protected $passengerInfo;
    protected $segments;
    protected $associatedRecords;
    protected $bookingInfo;
    protected $seatsInfo;
    protected $luggageInfo ;
    protected $booking_data;

    public function __construct($bookingId, $flightOrderId, TicketService $ticketService)
    {
        $this->bookingId = $bookingId;
        $this->flightOrderId = $flightOrderId;
        $this->ticketService = $ticketService;

        //fetching all necessary data
        $ticketData = $this->ticketService->getTicketData($bookingId, $flightOrderId);

        $this->passengerInfo = $ticketData['passengerInfo'];
        $this->flightDetails = $ticketData['flightDetails'];
        $this->seatsInfo = $ticketData['seatsInfo'];
        $this->luggageInfo = $ticketData['luggageInfo'];
        $this->status = $ticketData['status'];
        $this->flightOrderId = $ticketData['flightOrderId'];
        $this->queuingOfficeId = $ticketData['queuingOfficeId'];
        $this->reference = $ticketData['reference'];
        $this->booking_data = $ticketData['booking_data'];
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $pdf = $this->ticketService->generatePdf($this->bookingId);

        return $this->view('emails.tickets')
            ->attachData($pdf, 'ticket.pdf', [
                'mime' => 'application/pdf',
                //Multipurpose Internet Mail Extensions.
                //it is a standard used to define the nature and format of a document or file
                //the MIME type 'application/pdf' indicates that the attached file is a PDF document.
            ])
            ->with([
                'bookingId' => $this->bookingId,
                'passengerInfo' => $this->passengerInfo,
                'segments' => $this->segments,
                'associatedRecords' => $this->associatedRecords,
                'bookingInfo' => $this->bookingInfo,
                'seatsInfo' => $this->seatsInfo,
                'luggageInfo' => $this->luggageInfo,
                'booking_data' => $this->booking_data,
            ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }


    /**
     * @throws \Exception
     */

    public  static  function sendTicketEmail($bookingId, $flightOrderId): void
    {
        $ticketService = new TicketService();
        $booking = Booking::find($bookingId);
        if ($booking) {
            $passengers = json_decode($booking->passenger_info, true);
            if (is_array($passengers)) {
                foreach ($passengers as $passenger) {
                    if (isset($passenger['email'])) {
                        $passengerEmail = $passenger['email'];
                        Mail::to($passengerEmail)->send(new TicketMail($bookingId, $flightOrderId, $ticketService));
                        Log::info('Ticket email sent successfully', [
                            'bookingId' => $bookingId,
                            'email' => $passengerEmail,
                        ]);
                    } else {
                        Log::error('Email key not found for a passenger in booking ID: ' . $bookingId);
                    }
                }
            } else {
                Log::error('Invalid passenger_info format for booking ID: ' . $bookingId);
                throw new \Exception('Invalid passenger_info format.');
            }
        }
    }

}
