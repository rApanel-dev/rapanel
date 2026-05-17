<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GameAccountAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = GameAccount::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('userid', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        if ($request->filled('gender')) {
            $query->where('sex', $request->gender);
        }

        $accounts = $query->orderByDesc('account_id')->paginate(25)->withQueryString();

        // Resolve master user names (cross-DB, 2 queries max)
        $masterIds = $accounts->pluck('master_id')->filter()->unique()->values();
        $masterUsers = User::whereIn('id', $masterIds)->pluck('name', 'id');

        $accounts->each(function ($account) use ($masterUsers) {
            $account->master_name = $masterUsers->get($account->master_id);
        });

        return Inertia::render('Admin/GameAccounts/Index', [
            'accounts' => $accounts,
            'filters'  => $request->only(['search', 'state', 'gender']),
        ]);
    }
}
