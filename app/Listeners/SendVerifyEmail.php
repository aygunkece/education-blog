<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\VerifyMail;
use App\Models\UserVerify;
use App\Notifications\VerifyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendVerifyEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        $token = \Illuminate\Support\Str::random("60");
        UserVerify::create([
            "user_id" => $event->user->id,
            "token" => $token
        ]);
        //Mail Gönderme işlemi
        $event->user->notify(new VerifyNotification($token));
        //Mail::to($event->user->email)->send(new VerifyMail($event->user, $token));
    }
}
