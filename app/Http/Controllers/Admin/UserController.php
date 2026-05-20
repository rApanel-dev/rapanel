<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('last_ip', 'like', "%{$term}%")
                  ->orWhere('country', 'like', "%{$term}%")
                  ->orWhere('birthdate', 'like', "%{$term}%");

                if (is_numeric($term)) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Expose fields hidden by default — safe in admin-only context
        $users->getCollection()->transform(function ($user) {
            return $user->makeVisible(['api_token', 'remember_token', 'email_verified_at'])
                        ->append([])
                        ->setAttribute('has_two_factor', !empty($user->getRawOriginal('two_factor_secret')));
        });

        return Inertia::render('Admin/MasterAccounts/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $isSelf = $user->id === $request->user()->id;

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,email,' . $user->id,
            'country'          => 'nullable|string|max:2',
            'birthdate'        => 'nullable|date',
            'donation_points'  => 'nullable|integer|min:0',
            'vote_points'      => 'nullable|integer|min:0',
            'role'             => 'nullable|in:User,Admin',
            'status'           => 'nullable|in:0,1',
            'password'         => 'nullable|string|min:8|confirmed',
        ]);

        $payload = array_filter([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'country'         => $data['country'] ?? null,
            'birthdate'       => $data['birthdate'] ?? null,
            'donation_points' => $data['donation_points'] ?? 0,
            'vote_points'     => $data['vote_points'] ?? 0,
        ], fn($v) => $v !== null);

        if (!$isSelf && isset($data['role']))   { $payload['role']   = $data['role']; }
        if (!$isSelf && isset($data['status'])) { $payload['status'] = (int) $data['status']; }

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return back()->with('success', 'User updated successfully.');
    }

    public function clearMfa(Request $request, User $user)
    {
        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return back()->with('success', "MFA cleared for {$user->name}.");
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:User,Admin']);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', "Role updated to {$request->role}.");
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate(['status' => 'required|in:0,1']);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['status' => 'You cannot change your own status.']);
        }

        $newStatus = (int) $request->status;
        $gameState = $newStatus ? 0 : 5;

        // Update panel status + invalidate remember-me cookie
        $user->forceFill(['status' => $newStatus, 'remember_token' => null])->save();

        // Invalidate all active web sessions
        DB::connection('mysql')->table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        // Update game accounts state
        $affectedAccounts = GameAccount::where('master_id', $user->id)
            ->pluck('userid', 'account_id')
            ->toArray();

        if (!empty($affectedAccounts)) {
            GameAccount::where('master_id', $user->id)->update(['state' => $gameState]);
        }

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'MASTER_ACCOUNT',
            'action'     => $newStatus ? 'master_account_activated' : 'master_account_banned',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'target_user_id'   => $user->id,
                'target_user_name' => $user->name,
                'target_user_email'=> $user->email,
                'game_accounts'    => $affectedAccounts,
            ],
        ]);

        return back()->with('success', $newStatus
            ? 'Account activated (game accounts re-enabled).'
            : 'Account banned (game accounts blocked).'
        );
    }
}
