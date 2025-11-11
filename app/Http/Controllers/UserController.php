<?php 

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;

class UserController extends Controller{

    public function __construct(protected AuthService $authService){}

    function register(RegisterRequest $request){
        try {

            [$user]= $this->authService->authRegister($request->name, $request->email,$request->password);


        } catch (\Throwable $th) {
           
        }


    }






}