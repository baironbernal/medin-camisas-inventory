<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Return a consistent user payload for login & register responses.
     */
    private function userPayload(User $user): array
    {
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'phone_number' => $user->phone_number,
        ];
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|string|email|max:255|unique:users',
            'first_name'       => 'nullable|string|max:255',
            'phone_number'     => 'required|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'password'         => 'required|string|min:8',
            'whatsapp_number'  => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:255',
            'selling_channel'  => 'nullable|string|max:255',
            'clothing_type'    => 'nullable|string|max:255',
            'selling_location' => 'nullable|string|max:255',
            'business_name'    => 'nullable|string|max:255',
        ]);

        $nameParts = explode(' ', trim($request->name), 2);

        $user = User::create([
            'name'             => $request->name,
            'first_name'       => $request->first_name ?? ($nameParts[0] ?? $request->name),
            'last_name'        => $request->last_name  ?? ($nameParts[1] ?? ''),
            'phone_number'     => $request->phone_number,
            'email'            => $request->email,
            'password'         => Hash::make($request->password),
            'whatsapp_number'  => $request->whatsapp_number,
            'city'             => $request->city,
            'selling_channel'  => $request->selling_channel,
            'clothing_type'    => $request->clothing_type,
            'selling_location' => $request->selling_location,
            'business_name'    => $request->business_name,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $this->userPayload($user),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Always run Hash::check() — even for non-existent users — so the
        // response time is identical whether the email exists or not.
        // Without this, timing differences reveal which emails are registered.
        $hashToCheck = $user?->password ?? Hash::make('dummy-prevent-timing-attack');

        if (!$user || !Hash::check($request->password, $hashToCheck)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
