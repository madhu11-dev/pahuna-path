<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\ErrorMessages\ErrorResource;
use App\Http\Resources\UserResources\RegistrationResource;
use App\Services\AuthService;
use Exception;
use Throwable;

class UserController extends Controller
{

    public function __construct(protected AuthService $authService) {}

    function register(RegisterRequest $request)
    {


        $validaor = $request->validated();
        $user = $this->authService->authRegister($validaor
            
        );

        return (new RegistrationResource((object)[
            'user'             => $user,
            // 'verificationurl'  => $verificationUrl
        ]))->response()->setStatusCode(200);
    }
}
