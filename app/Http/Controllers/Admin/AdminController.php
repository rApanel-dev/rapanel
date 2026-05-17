<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\GameAccount;
use App\Models\User;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users'           => User::count(),
            'active_users'          => User::where('status', 1)->count(),
            'banned_users'          => User::where('status', 0)->count(),
            'admin_users'           => User::where('role', 'Admin')->count(),
            'total_game_accounts'   => GameAccount::count(),
            'active_game_accounts'  => GameAccount::where('state', 0)->count(),
            'logs_today'            => ActionLog::whereDate('created_at', today())->count(),
            'logs_total'            => ActionLog::count(),
        ];

        $recentLogs = ActionLog::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentUsers = User::orderByDesc('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats'       => $stats,
            'recentLogs'  => $recentLogs,
            'recentUsers' => $recentUsers,
        ]);
    }
}
