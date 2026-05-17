<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = ActionLog::with('user');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->search}%"));
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $logs = $query->orderByDesc('created_at')->paginate(30)->withQueryString();
        $categories = ActionLog::distinct()->pluck('category')->filter()->sort()->values();

        return Inertia::render('Admin/Logs/Index', [
            'logs'       => $logs,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'category']),
        ]);
    }
}
