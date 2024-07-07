<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserProfileController extends Controller
{
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user;

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $orders = $user->orders;

            $profile = $user->only(['id', 'username', 'email', 'first_name', 'last_name', 'street', 'city', 'state', 'landmark']);
            $profile['orders'] = $orders;

            return response()->json(['profile' => $profile], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the profile'], 500);
        }
    }

    public function changeName(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
        ]);

        try {
            $user = $request->user;

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->save();

            return response()->json(['message' => 'Names updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating names'], 500);
        }
    }

    public function changeUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
        ]);

        try {
            $user = $request->user;

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->username = $request->username;
            $user->save();

            return response()->json(['message' => 'Username updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the username'], 500);
        }
    }

    public function changeEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|unique:users,email',
        ]);

        try {
            $user = $request->user;

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->email = $request->email;
            $user->save();

            return response()->json(['message' => 'Email updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the email'], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        try {
            $user = $request->user;

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Invalid current password'], 401);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['message' => 'Password changed successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while changing the password'], 500);
        }
    }

    public function changeAddress(Request $request)
    {
        $request->validate([
            'street' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'landmark' => 'required|string',
        ]);

        try {
            $user = $request->user;

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->street =  $request->street;
            $user->city =  $request->city;
            $user->state =  $request->state;
            $user->landmark =  $request->landmark;

            $user->save();

            return response()->json(['message' => 'Address details updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating address details'], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $resetToken = Str::random(60);
            $user->reset_password_token = $resetToken;
            $user->reset_password_expires_at = now()->addHour();
            $user->save();

            $url = env('CLIENT_URL') . '/reset-password?token=' . $resetToken;

            Mail::raw('Click here to reset your password: ' . $url, function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Password Reset');
            });

            return response()->json(['message' => 'Password reset email sent successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while sending the password reset email'], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user = User::where('reset_password_token', $request->token)
                        ->where('reset_password_expires_at', '>', now())
                        ->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid or expired reset token'], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->reset_password_token = null;
            $user->reset_password_expires_at = null;
            $user->save();

            return response()->json(['message' => 'Password reset successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while resetting the password'], 500);
        }
    }
}
