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

    public $name;
    public $email;
    public $value;
    public $date;
    public $document_number;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email, $value, $date, $document_number)
    {
        $this->name = $name;
        $this->email = $email;
        $this->value = $value;
        $this->date = $date;
        $this->document_number = $document_number;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('nao-responda@anipp.org.br', 'ANIPP'),
            subject: 'Valor incomum identificado',
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
                'name' => $this->name,
                'email' => $this->email,
                'value' => $this->value,
                'date' => $this->date,
                'document_number' => $this->document_number
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
