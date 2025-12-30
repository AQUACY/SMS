<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;

class PaymentReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Payment $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Receipt - {$this->payment->reference}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.payment-receipt',
            text: 'emails.payment-receipt-text',
            with: [
                'payment' => $this->payment,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Generate receipt PDF
        $pdf = $this->generateReceiptPdf();
        
        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "receipt_{$this->payment->reference}.pdf"
            )->withMime('application/pdf'),
        ];
    }

    /**
     * Generate receipt PDF
     */
    private function generateReceiptPdf()
    {
        $payment = $this->payment->load(['student', 'term', 'parent.user']);
        
        // Get school information
        $school = $payment->student->school ?? null;
        
        $data = [
            'payment' => $payment,
            'school' => $school,
            'generated_at' => now(),
        ];

        // Generate PDF with 80mm x 80mm paper size (POS receipt format)
        // 80mm = 226.77 points (1mm = 2.83465 points)
        $pdf = PdfFacade::loadView('receipts.pos', $data);
        $pdf->setPaper([0, 0, 226.77, 226.77], 'portrait'); // 80mm x 80mm in points
        
        return $pdf;
    }
}

