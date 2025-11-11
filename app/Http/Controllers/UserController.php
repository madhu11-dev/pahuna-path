<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\ErrorMessages\ErrorResource;
use App\Http\Resources\UserResources\RegistrationResource;
use App\Services\AuthService;
use Throwable;

class UserController extends Controller
{

    public function __construct(protected AuthService $authService) {}

    function register(RegisterRequest $request)
    {
        try {

            [$user, $verificationUrl] = $this->authService->authRegister(
                $request->name,
                $request->email,
                $request->password
            );

            return (new RegistrationResource((object)[
                'user'             => $user,
                'verificationurl'  => $verificationUrl
            ]))->response()->setStatusCode(200);
        } catch (Throwable $e) {
            return (new ErrorResource((object)[
                'message' => $e->getMessage()
            ]))->response()->setStatusCode(400);
        }
    }
}
