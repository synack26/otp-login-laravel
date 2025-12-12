<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\OtpService;
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
    protected $otpService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(OtpService $otpService)
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
        $this->otpService = $otpService;
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
        $otpData = $this->otpService->generateOtp($user->id);

        // Store user_id in session temporarily (not logged in yet)
        $request->session()->put('pending_login_user_id', $user->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => 'OTP sent. Please verify to complete login.',
                'otp' => (int) $otpData['otp'],
                'user_id' => $user->id,
                'expires_at' => $otpData['expired_at']->toDateTimeString()
            ]);
        }

        return redirect()->route('otp.verification', ['user_id' => $user->id])
            ->with('success', 'Please enter the OTP to complete login');
    }
}

