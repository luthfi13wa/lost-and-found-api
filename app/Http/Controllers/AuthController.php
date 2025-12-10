<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'api_token' => Str::random(60),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $user->api_token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password'=> 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json([
            'user' => $user,
            'token' => $user->api_token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(auth()->user());
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $user->api_token = null;
            $user->save();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
