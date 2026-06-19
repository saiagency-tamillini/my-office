<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $backupPath,
        public string $database,
        public string $date
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name')." DB Backup - {$this->date}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.database_backup',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $filename = basename($this->backupPath);

        return [
            Attachment::fromPath($this->backupPath)
                ->as($filename)
                ->withMime('application/gzip'),
        ];
    }
}
