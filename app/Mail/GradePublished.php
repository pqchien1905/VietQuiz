<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GradePublished extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $studentName,
        public $quizTitle,
        public $score,
        public $totalPoints,
        public $pct,
        public string $itemType = 'quiz',
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->itemType === 'assignment' ? 'Bai tap' : 'Bai kiem tra';

        return new Envelope(
            subject: "{$label} da duoc cham - VietQuiz",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.grade-published',
        );
    }
}
