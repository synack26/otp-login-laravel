<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Override login method to add OTP verification step
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Check if credentials are valid
        $credentials = $this->credentials($request);
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !\Hash::check($credentials['password'], $user->password)) {
            return $this->sendFailedLoginResponse($request);
        }

        // Credentials are valid, now generate OTP instead of logging in
        $verificationCode = $this->generateOtp($user);

        // Store user_id in session temporarily (not logged in yet)
        $request->session()->put('pending_login_user_id', $user->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => 'OTP sent. Please verify to complete login.',
                'otp' => (int) $verificationCode->otp,
                'user_id' => $user->id,
                'expires_at' => $verificationCode->expired_at->toDateTimeString()
            ]);
        }

        return redirect()->route('otp.verification', ['user_id' => $user->id])
            ->with('success', 'Please enter the OTP to complete login');
    }

    /**
     * Generate OTP for user
     */
    protected function generateOtp($user)
    {
        // Expire all previous OTPs for this user
        VerificationCode::where('user_id', $user->id)
            ->where('expired_at', '>', Carbon::now())
            ->update(['expired_at' => Carbon::now()]);

        // Create a new OTP
        return VerificationCode::create([
            'user_id' => $user->id,
            'otp' => rand(100000, 999999),
            'expired_at' => Carbon::now()->addMinutes(10)
        ]);
    }
}

