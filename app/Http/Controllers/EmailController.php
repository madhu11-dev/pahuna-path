<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Models\User;

class EmailController extends Controller
{
    public function __construct(protected EmailService $emailService) {}

    public function sendMail(Request $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();
        return $this->emailService->sendMail($user);
    }

    public function verification(Request $request, $id, $hash)
    {
        return $this->emailService->verification($id, $hash);
    }
}
