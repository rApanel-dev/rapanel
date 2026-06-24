<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'      => 'required|string|min:3|max:255',
            'email'     => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'country'   => 'required|string|size:2',
            'birthdate' => 'required|date|before:today',
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'country'   => $request->country,
            'birthdate' => $request->birthdate,
            'password'  => Hash::make($request->password),
            'locale'    => session('locale') ?? config('app.locale'),
        ]);

        event(new Registered($user));

        Auth::login($user);

        ActionLog::create([
            'user_id'    => $user->id,
            'category'   => 'MASTER_ACCOUNT',
            'action'     => 'account_registered',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => ['email' => $user->email],
        ]);

        return redirect(route('dashboard', absolute: false));
    }
}
