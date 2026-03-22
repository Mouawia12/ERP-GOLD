<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\LoginModeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Validator;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginModeService $loginModeService,
    ) {
    }

    public function login(Request $request)
    {
        try {
            $rules = [
                'email' => 'required|email',
                'password' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error validator',
                    'errors' => $validator->errors()->all(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::query()->where('email', $request->input('email'))->first();

            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials!',
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (! $user->status) {
                return response()->json([
                    'message' => 'User account is inactive.',
                ], Response::HTTP_FORBIDDEN);
            }

            if ($this->loginModeService->isSingleDeviceMode()) {
                $user->tokens()->delete();
            }

            $tokenResult = $user->createToken('token');
            $token = $tokenResult->plainTextToken;
            $tokenId = $tokenResult->accessToken->id;
            $this->loginModeService->syncApiToken($user, $tokenId);
            $cookie = cookie('jwt', $token, 60 * 24);

            return response()->json([
                'message' => $token,
                'token' => $token,
                'login_mode' => $this->loginModeService->currentMode(),
                'user' => $user,
            ])->withCookie($cookie);
        } catch (\Exception $ex) {
            return response()->json([
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        if (! $user || ! $this->loginModeService->isApiTokenValid($user, $currentToken?->id)) {
            optional($currentToken)->delete();

            return response()->json([
                'message' => 'Session expired or replaced by another device.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'user' => $user,
            'token_id' => $currentToken?->id,
        ]);
    }

    public function logout(Request $request)
    {
        try { 
                $cookie = Cookie::forget('jwt'); 
                $user = $request->user();
                $currentToken = $user?->currentAccessToken();

                if ($user && $currentToken && ! $this->loginModeService->isApiTokenValid($user, $currentToken->id)) {
                    $currentToken->delete();

                    return response()->json([
                        'message' => 'Session expired or replaced by another device.',
                    ], Response::HTTP_UNAUTHORIZED)->withCookie($cookie);
                }

                if ($currentToken) {
                    $tokenId = $currentToken->id;
                    $currentToken->delete();
                    $this->loginModeService->clearApiToken($user, $tokenId);
                }

                return response()->json([
                    'message' => 'Success loggedOut',
                ])->withCookie($cookie);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Error loggedOut',
            ]);
        }
    }
}
