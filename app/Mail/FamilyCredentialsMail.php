<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FamilyCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;


        Log::info('FamilyCredentialsMail constructed with:', [
            'user' => $this->user,
            'password' => $this->password
        ]);
    }

    public function build()
    {
        return $this->subject('Your Family Account Credentials')
                    ->view('emails.family-credentials')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password,
                    ]);
    }
}