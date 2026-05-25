<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeEmailRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\GameAccount;
use App\Models\User;
use App\Models\UserLog;
use App\Notifications\AccountDeletionNotification;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail'    => $request->user() instanceof MustVerifyEmail
                                    && config('services.ra.require_email_verify', false),
            'emailVerified'      => $request->user()->hasVerifiedEmail(),
            'requireEmailVerify' => config('services.ra.require_email_verify', false),
            'status'             => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        foreach (['name', 'birthdate', 'country', 'locale'] as $field) {
            if (!array_key_exists($field, $validated)) {
                continue;
            }
            $newVal = $validated[$field];
            $oldVal = $user->$field;
            if ((string) $oldVal !== (string) $newVal && !($oldVal === null && $newVal === null)) {
                UserLog::create([
                    'user_id'    => $user->id,
                    'field'      => $field,
                    'old_value'  => $oldVal,
                    'new_value'  => $newVal,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        }

        $user->fill($validated)->save();

        // Sincronizar idioma preferido con la sesión para que la UI cambie de inmediato
        if (!empty($validated['locale'])) {
            session(['locale' => $validated['locale']]);
            app()->setLocale($validated['locale']);
        }

        return Redirect::route('profile.edit');
    }

    public function changeEmail(ChangeEmailRequest $request): RedirectResponse
    {
        $user     = $request->user();
        $oldEmail = $user->email;
        $newEmail = $request->validated('email');

        $user->email             = $newEmail;
        $user->email_verified_at = null;
        $user->save();

        UserLog::create([
            'user_id'    => $user->id,
            'field'      => 'email',
            'old_value'  => $oldEmail,
            'new_value'  => $newEmail,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        GameAccount::where('master_id', $user->id)->update(['email' => $newEmail]);

        if (config('services.ra.require_email_verify', false)) {
            $notification = new SecurityAlertNotification(
                'email_changed',
                $request->ip(),
                now()->format('d/m/Y H:i:s'),
            );
            if ($user->locale) {
                $notification = $notification->locale($user->locale);
            }
            Notification::route('mail', $oldEmail)->notify($notification);

            $user->sendEmailVerificationNotification();

            return Redirect::route('verification.notice')
                ->with('status', __('Email updated. Please verify your new email address.'));
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(
            ['password' => ['required', 'current_password']],
            ['password.current_password' => __('The provided password does not match your current password.')]
        );

        $user = $request->user();

        if (config('services.ra.require_email_verify', false)) {
            $url = URL::temporarySignedRoute(
                'profile.delete-confirm',
                Carbon::now()->addMinutes(15),
                [
                    'id'   => $user->id,
                    'hash' => sha1($user->email . $user->id . 'delete'),
                ]
            );

            $notification = new AccountDeletionNotification(
                $url,
                now()->format('d/m/Y H:i:s'),
                $request->ip(),
            );

            if ($user->locale) {
                $notification = $notification->locale($user->locale);
            }

            $user->notify($notification);

            return back()->with('status', 'deletion-email-sent');
        }

        foreach (GameAccount::where('master_id', $user->id)->get() as $gameAccount) {
            $gameAccount->update([
                'userid'    => 'del_' . $gameAccount->account_id,
                'master_id' => null,
                'state'     => 5,
            ]);
        }

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function confirmDeletion(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->email . $user->id . 'delete'))) {
            abort(403);
        }

        // Terminate all active sessions on every device immediately
        DB::table('sessions')->where('user_id', $user->id)->delete();

        foreach (GameAccount::where('master_id', $user->id)->get() as $gameAccount) {
            $gameAccount->update([
                'userid'    => 'del_' . $gameAccount->account_id,
                'master_id' => null,
                'state'     => 5,
            ]);
        }

        $user->delete();

        return Redirect::route('login')
            ->with('status', __('Your account has been permanently deleted.'));
    }
}
