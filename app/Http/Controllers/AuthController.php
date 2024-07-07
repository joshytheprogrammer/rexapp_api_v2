<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Mail\WelcomeMail;
use App\Mail\WelcomeBackMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Username/Email and password are required!'], 400);
        }

        $credentials = $request->only('identifier', 'password');
        $user = User::where('email', $credentials['identifier'])->orWhere('username', $credentials['identifier'])->first();

        // return response()->json($user->password);
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid username or password!'], 401);
        }

        $token = JWTAuth::fromUser($user);

        // $this->sendLoginMail($user->username, $user->email);

        return response()->json([
            'accessToken' => $token,
            'refreshToken' => $this->createRefreshToken($user)
        ], 200);
    }

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => false,
        ]);

        // $this->sendNewUserMail($user->username, $user->email);

        return response()->json(['message' => 'User created successfully!'], 201);
    }

    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    public function refresh(Request $request)
    {
        $token = $request->input('refreshToken');
        if (!$token) {
            return response()->json(['message' => 'Refresh token is required!'], 401);
        }

        try {
            $token = JWTAuth::refresh($token);
            return response()->json(['accessToken' => $token], 200);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Invalid refresh token!'], 401);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Logged out successfully!'], 200);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Invalid refresh token!'], 401);
        }
    }

    protected function createRefreshToken($user)
    {
        return JWTAuth::fromUser($user, ['exp' => now()->addDays(180)->timestamp]);
    }

    protected function sendNewUserMail($username, $email)
    {
        $emailDetails = [
            'username' => $username,
        ];

        Mail::to($email)->send(new WelcomeMail($emailDetails));

        Mail::to('admin@rexapp.ng')->send(new WelcomeMail($emailDetails, true));
    }

    protected function sendLoginMail($username, $email)
    {
        $emailDetails = [
            'username' => $username,
        ];

        Mail::to($email)->send(new WelcomeBackMail($emailDetails));
    }

}
