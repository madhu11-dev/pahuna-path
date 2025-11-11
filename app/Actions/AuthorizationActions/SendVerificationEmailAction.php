<?php 
namespace App\Actions\AuthorizationActions;

use Illuminate\Auth\Events\Registered;

class SendVerificationEmailAction{

    public function sendEmail($user){
        event(new Registered($user));

        $verificationUrl = route('verification.verify',[
            'id'=> $user->id,
            'hash'=> sha1($user->email),


        ]);

        return $verificationUrl;
    }
}