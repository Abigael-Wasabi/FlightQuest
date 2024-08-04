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

    public function __construct($bookingId, TicketService $ticketService)
    {
        $this->bookingId = $bookingId;
        $this->ticketService = $ticketService;
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

    public  static  function sendTicketEmail($bookingId): void
    {
        $ticketService = new TicketService();
        $booking = Booking::find($bookingId);
        if ($booking) {
            $passengers = json_decode($booking->passenger_info, true);
            if (is_array($passengers)) {
                foreach ($passengers as $passenger) {
                    if (isset($passenger['email'])) {
                        $passengerEmail = $passenger['email'];
                        Mail::to($passengerEmail)->send(new TicketMail($bookingId, $ticketService));
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
