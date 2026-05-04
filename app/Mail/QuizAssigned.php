<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $studentName,
        public $quizTitle,
        public $className,
        public $dueDate,
        public $quizUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📝 Bài kiểm tra mới được giao - VietQuiz",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quiz-assigned',
        );
    }
}
