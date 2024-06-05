<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotIdentifierPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $infos;

    /**
     * Create a new message instance.
     */
    public function __construct($infos)
    {
        $this->infos = $infos;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('nao-responda@anipp.org.br', 'ANIPP'),
            subject: 'Valores incomums identificados na leitura do extrato',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'mails.not-identifier-payment',
            with: [
                'infos' => $this->infos
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
        return [];
    }
}
