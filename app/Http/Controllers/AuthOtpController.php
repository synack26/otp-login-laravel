<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthOtpController extends Controller
{
    // Return View of OTP Login Page
    public function login()
    {
        return view('auth.otp-login');
    }

    // Generate OTP
    public function generate(Request $request)
    {
        # Validate Data
        $request->validate([
            'mobile_no' => 'required|exists:users,mobile_no'
        ]);

        # Generate An OTP
        $verificationCode = $this->generateOtp($request->mobile_no);

        //$message = "Your OTP To Login is - " . $verificationCode->otp;
        $message = "Enter your OTP to login";

         # Return With OTP as JSON if request expects JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $message, 
                'otp' => (int) $verificationCode->otp,
                'user_id' => $verificationCode->user_id,
                'expires_at' => $verificationCode->expired_at->toDateTimeString()
            ]);
        }

        # Otherwise, redirect to the verification page
        return redirect()->route('otp.verification', ['user_id' => $verificationCode->user_id])->with('success', $message);
    }

    public function generateOtp($mobile_no)
    {
        $user = User::where('mobile_no', $mobile_no)->first();

        # Expire all previous OTPs for this user
        VerificationCode::where('user_id', $user->id)
            ->where('expired_at', '>', Carbon::now())
            ->update(['expired_at' => Carbon::now()]);

        // Always create a new OTP for every login attempt
        return VerificationCode::create([
            'user_id' => $user->id,
            'otp' => rand(100000, 999999), // Ensure 6-digit integer
            'expired_at' => Carbon::now()->addMinutes(10)
        ]);
    }

    public function verification($user_id)
    {
        return view('auth.otp-verification')->with([
            'user_id' => $user_id
        ]);
    }

    public function loginWithOtp(Request $request)
    {
        #Validation
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required'
        ]);

        #Validation Logic
        $verificationCode = VerificationCode::where('user_id', $request->user_id)->where('otp', $request->otp)->first();

        $now = Carbon::now();
        if (!$verificationCode) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Your OTP is not correct'], 400);
            }
            return redirect()->back()->with('error', 'Your OTP is not correct');
        } elseif ($verificationCode && $now->isAfter($verificationCode->expired_at)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Your OTP has been expired'], 401);
            }
            return redirect()->route('otp.login')->with('error', 'Your OTP has been expired');
        }

        $user = User::whereId($request->user_id)->first();

        if ($user) {
            // Expire The OTP
            $verificationCode->update([
                'expired_at' => Carbon::now()
            ]);

            Auth::login($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => 'Login successful',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'mobile_no' => $user->mobile_no
                    ]
                ]);
            }

            return redirect('/home');
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Your OTP is not correct'], 400);
        }
        return redirect()->route('otp.login')->with('error', 'Your Otp is not correct');
    }




    public function registerWithOtp(Request $request)
    {
        // Validate OTP
        $verificationCode = VerificationCode::find(Session::get('verification_code_id'));
        if (!$verificationCode || $verificationCode->otp !== $request->otp) {
            return redirect()->back()->with('error', 'Invalid OTP');
        }

        // Clear session data
        Session::forget(['user_id', 'verification_code_id']);

        // Proceed with user registration
        $user = User::find(Session::get('user_id'));
        Auth::login($user);

        return redirect()->route('home');
    }
}
