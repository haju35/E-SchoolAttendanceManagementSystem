<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StudentCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;


        Log::info('StudentCredentialsMail constructed with:', [
            'user' => $this->user,
            'password' => $this->password
        ]);
    }

    public function build()
    {
        return $this->subject('Your Student Account Credentials')
                    ->view('emails.student-credentials')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password,
                    ]);
    }
}