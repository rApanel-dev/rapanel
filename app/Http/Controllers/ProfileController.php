<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\GameAccount;
use App\Models\UserLog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user      = $request->user();
        $validated = $request->validated();

        $trackedFields = ['name', 'email', 'birthdate'];
        $changes = [];

        foreach ($trackedFields as $field) {
            if (!array_key_exists($field, $validated)) {
                continue;
            }
            $newVal = $validated[$field];
            $oldVal = $user->$field;

            if ((string) $oldVal !== (string) $newVal && !($oldVal === null && $newVal === null)) {
                $changes[$field] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        $user->fill($validated);

        if (isset($changes['email'])) {
            $user->email_verified_at = null;
        }

        $user->save();

        foreach ($changes as $field => $vals) {
            UserLog::create([
                'user_id'    => $user->id,
                'field'      => $field,
                'old_value'  => $vals['old'],
                'new_value'  => $vals['new'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $gameAccountSync = array_intersect_key(
            ['email' => $user->email, 'birthdate' => $user->birthdate],
            $changes
        );

        if (!empty($gameAccountSync)) {
            GameAccount::where('master_id', $user->id)->update($gameAccountSync);
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
