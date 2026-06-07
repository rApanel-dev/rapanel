<?php

namespace App\Http\Controllers;

use App\Mail\GamePasswordResetMail;
use App\Models\ActionLog;
use App\Models\GamePasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ForgotGamePasswordController extends Controller
{
    public function create()
    {
        return Inertia::render('ForgotGamePassword');
    }

    public function store(Request $request)
    {
        $request->validate([
            'userid' => ['required', 'string', 'max:23'],
            'email'  => ['required', 'email', 'max:39'],
        ]);

        $key = 'game-pwd-reset:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            return back()->withErrors([
                'userid' => __('Too many attempts. Please try again in :minutes minutes.', ['minutes' => $minutes]),
            ]);
        }

        RateLimiter::hit($key, 3600);

        $account = DB::connection('mysql_main')
            ->table('login')
            ->where('userid', $request->userid)
            ->where('email', $request->email)
            ->where('state', 0)
            ->whereNull('master_id')
            ->select('account_id', 'userid', 'email')
            ->first();

        // Mensaje genérico independientemente del resultado para no revelar si existe
        $genericMessage = __('If that account exists and the email matches, a reset link has been sent.');

        if (!$account) {
            return back()->with('success', $genericMessage);
        }

        // Invalida tokens previos no usados para esta cuenta
        GamePasswordReset::where('account_id', $account->account_id)
            ->where('used', false)
            ->update(['used' => true]);

        $token = Str::random(64);

        GamePasswordReset::create([
            'user_id'    => Auth::id(),
            'account_id' => $account->account_id,
            'token'      => $token,
            'used'       => false,
            'expires_at' => now()->addMinutes(30),
            'request_ip' => $request->ip(),
        ]);

        $resetUrl   = route('game-password.reset', ['token' => $token]);
        $serverName = config('services.ra.server_name', config('app.name'));

        Mail::to($account->email)->send(
            (new GamePasswordResetMail($account->userid, $resetUrl, $serverName))->locale(app()->getLocale())
        );

        return back()->with('success', $genericMessage);
    }

    public function reset(string $token)
    {
        $record = GamePasswordReset::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return Inertia::render('ForgotGamePassword', [
                'tokenError' => __('This password reset link is invalid or has expired.'),
            ]);
        }

        $account = DB::connection('mysql_main')
            ->table('login')
            ->where('account_id', $record->account_id)
            ->select('account_id', 'userid')
            ->first();

        return Inertia::render('ResetGamePassword', [
            'token'  => $token,
            'userid' => $account?->userid ?? '',
        ]);
    }

    public function update(Request $request, string $token)
    {
        $request->validate([
            'password'              => ['required', 'string', 'min:4', 'max:32'],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        $record = GamePasswordReset::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return back()->withErrors(['password' => __('This password reset link is invalid or has expired.')]);
        }

        $newPassword = $request->password;
        if (config('services.ra.use_md5')) {
            $newPassword = md5($newPassword);
        }

        DB::connection('mysql_main')
            ->table('login')
            ->where('account_id', $record->account_id)
            ->update(['user_pass' => $newPassword]);

        $record->update(['used' => true]);

        ActionLog::create([
            'user_id'    => $record->user_id,
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_password_reset',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => ['account_id' => (int) $record->account_id, 'method' => 'email'],
        ]);

        return redirect()->route('dashboard')
            ->with('success', __('Game account password has been reset successfully.'));
    }
}
