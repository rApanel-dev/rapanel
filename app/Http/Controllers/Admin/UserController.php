<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function show(User $user)
    {
        $gameAccounts = $user->gameAccounts()->get();

        $recentLogs = ActionLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'user'         => $user,
            'gameAccounts' => $gameAccounts,
            'recentLogs'   => $recentLogs,
        ]);
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

        $user->update(['status' => $request->status]);

        return back()->with('success', $request->status ? 'Account activated.' : 'Account banned.');
    }
}
