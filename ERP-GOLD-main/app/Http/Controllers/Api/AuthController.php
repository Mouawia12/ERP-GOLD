<?php

namespace App\Http\Controllers\Api;

use App\Models\User; 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;  
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth; 
use Validator;


class AuthController extends Controller
{

    public function login(Request $request)
    {
        try {

            $rules = [
                "email" => "required",
                "password" => "required"

            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) { 
                return  response()->json([
                    'message' => 'Error validator'
                ]);
            }

            $credentials = $request->only(['email', 'password']);
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials!'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = Auth::user();
            $token = $user->createToken('token')->plainTextToken;
            $cookie = cookie('jwt', $token, 60 * 24); // 1 day 

            return response()->json([
                'message' => $token,
                'user'=> $user
            ])->withCookie($cookie);
            /*
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'required',
            ]);
         
            $user = User::where('email', $request->email)->first();
         
            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
             return $user->createToken($request->device_name)->plainTextToken;
            */
        } catch (\Exception $ex) {
            return response()->json([
                'code' => $ex->getCode(),
                'message'=> $ex->getMessage()
            ]);
        }

    }

    public function user()
    { 
       return Auth::user()->currentAccessToken();
    }

    public function logout(Request $request)
    {
        try { 
                
                $cookie = Cookie::forget('jwt'); 

                # Revoke all tokens...
                //$user->tokens()->delete();
                # Revoke a specific token...
                //$user->tokens()->where('id', $tokenId)->delete();
                Auth::user()->currentAccessToken()->delete(); 
                //Auth::logout();
                return response()->json([
                    'message' => 'Success loggedOut'
                ])->withCookie($cookie);
           

        }catch (\Exception $ex){
            return  response()->json([
                'message' => 'Error loggedOut'
            ]);
        }

    }

 
}
