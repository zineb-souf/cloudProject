<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * REGISTER
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'sometimes|string|in:client,livreur,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'client',
        ]);

        // JWT CORRECT WAY
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => JWTAuth::user()
        ]);
    }

    /**
     * PROFILE
     */
    public function profile()
    {
        return response()->json([
            'status' => true,
            'user' => JWTAuth::user()
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}