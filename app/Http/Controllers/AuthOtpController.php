<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthOtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

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
            'email' => 'required|email|exists:users,email'
        ]);

        # Generate An OTP
        $user = User::where('email', $request->email)->first();
        $otpData = $this->otpService->generateOtp($user->id);

        $message = "Enter your OTP to login";

         # Return With OTP as JSON if request expects JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $message, 
                'otp' => (int) $otpData['otp'],
                'user_id' => $otpData['user_id'],
                'expires_at' => $otpData['expired_at']->toDateTimeString()
            ]);
        }

        # Otherwise, redirect to the verification page
        return redirect()->route('otp.verification', ['user_id' => $otpData['user_id']])->with('success', $message);
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

        #Validation Logic - Check if OTP exists in Redis
        if (!$this->otpService->verifyOtp($request->user_id, $request->otp)) {
            // Check if OTP is expired
            if ($this->otpService->isExpired($request->user_id)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Your OTP has been expired'], 401);
                }
                return redirect()->route('otp.login')->with('error', 'Your OTP has been expired');
            }
            
            // OTP is incorrect
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Your OTP is not correct'], 400);
            }
            return redirect()->back()->with('error', 'Your OTP is not correct');
        }

        $user = User::whereId($request->user_id)->first();

        if ($user) {
            // Expire The OTP from Redis
            $this->otpService->expireOtp($request->user_id);

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
        // Validate OTP using Redis
        if (!$this->otpService->verifyOtp(Session::get('user_id'), $request->otp)) {
            return redirect()->back()->with('error', 'Invalid OTP');
        }

        // Expire OTP from Redis
        $this->otpService->expireOtp(Session::get('user_id'));

        // Clear session data
        Session::forget(['user_id', 'verification_code_id']);

        // Proceed with user registration
        $user = User::find(Session::get('user_id'));
        Auth::login($user);

        return redirect()->route('home');
    }
}
