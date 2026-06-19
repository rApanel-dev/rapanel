<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\Donation;
use App\Models\DonationPackage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DonationAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Donation::with(['user', 'package', 'approver'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $donations = $query->paginate(20)->through(fn ($d) => [
            'id'                 => $d->id,
            'user_name'          => $d->user?->name,
            'user_email'         => $d->user?->email,
            'package_title'      => $d->package?->title,
            'platform'           => $d->platform,
            'transaction_id'     => $d->transaction_id,
            'amount_usd'         => (float) $d->amount_usd,
            'cashpoints_awarded' => $d->cashpoints_awarded,
            'status'             => $d->status,
            'notes'              => $d->notes,
            'approved_by_name'   => $d->approver?->name,
            'approved_at'        => $d->approved_at?->format('Y-m-d H:i'),
            'created_at'         => $d->created_at->format('Y-m-d H:i'),
            'created_ago'        => $d->created_at->diffForHumans(),
        ]);

        return Inertia::render('Admin/Donations/Index', [
            'donations' => $donations,
            'filters'   => $request->only(['status', 'platform', 'search', 'date_from', 'date_to']),
            'totals'    => [
                'all'       => Donation::count(),
                'pending'   => Donation::where('status', 'pending')->count(),
                'completed' => Donation::where('status', 'completed')->count(),
                'failed'    => Donation::where('status', 'failed')->count(),
            ],
        ]);
    }

    public function show(Donation $donation): Response
    {
        $donation->load(['user', 'package', 'approver']);

        return Inertia::render('Admin/Donations/Show', [
            'donation' => [
                'id'                 => $donation->id,
                'user_name'          => $donation->user?->name,
                'user_email'         => $donation->user?->email,
                'user_id'            => $donation->user_id,
                'package_title'      => $donation->package?->title,
                'package_id'         => $donation->package_id,
                'platform'           => $donation->platform,
                'transaction_id'     => $donation->transaction_id,
                'amount_usd'         => (float) $donation->amount_usd,
                'cashpoints_awarded' => $donation->cashpoints_awarded,
                'status'             => $donation->status,
                'metadata'           => $donation->metadata,
                'notes'              => $donation->notes,
                'approved_by_name'   => $donation->approver?->name,
                'approved_at'        => $donation->approved_at?->format('Y-m-d H:i'),
                'created_at'         => $donation->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function create(): Response
    {
        $users    = User::orderBy('name')->get(['id', 'name', 'email']);
        $packages = DonationPackage::active()->get(['id', 'title', 'price_usd', 'cashpoints', 'bonus_percent']);

        return Inertia::render('Admin/Donations/Create', [
            'users'    => $users,
            'packages' => $packages->map(fn ($p) => [
                'id'               => $p->id,
                'title'            => $p->title,
                'price_usd'        => (float) $p->price_usd,
                'cashpoints'       => $p->cashpoints,
                'bonus_percent'    => $p->bonus_percent,
                'total_cashpoints' => $p->totalCashpoints(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'package_id' => 'nullable|exists:donation_packages,id',
            'amount_usd' => 'required|numeric|min:0.01',
            'cashpoints' => 'required|integer|min:1',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($request->user_id);

        $donation = Donation::create([
            'user_id'           => $user->id,
            'package_id'        => $request->package_id,
            'platform'          => 'manual',
            'transaction_id'    => null,
            'amount_usd'        => $request->amount_usd,
            'cashpoints_awarded'=> $request->cashpoints,
            'status'            => 'pending',
            'notes'             => $request->notes,
        ]);

        $donation->markCompleted($request->cashpoints, $request->user());

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'DONATION',
            'action'     => 'donation_manual_created',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'target_user'  => $user->name,
                'amount_usd'   => (float) $request->amount_usd,
                'cashpoints'   => $request->cashpoints,
                'package_id'   => $request->package_id,
            ],
        ]);

        return redirect()->route('admin.donations.index')
            ->with('success', __('Manual donation added.'));
    }

    public function approve(Request $request, Donation $donation): RedirectResponse
    {
        if ($donation->status !== 'pending') {
            return back()->with('error', __('Only pending donations can be approved.'));
        }

        $donation->markCompleted($donation->cashpoints_awarded, $request->user());

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'DONATION',
            'action'     => 'donation_approved',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'donation_id' => $donation->id,
                'user_id'     => $donation->user_id,
                'cashpoints'  => $donation->cashpoints_awarded,
            ],
        ]);

        return back()->with('success', __('Donation approved.'));
    }

    public function reject(Request $request, Donation $donation): RedirectResponse
    {
        if ($donation->status === 'completed') {
            return back()->with('error', __('Completed donations cannot be rejected.'));
        }

        $donation->update(['status' => 'failed']);

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'DONATION',
            'action'     => 'donation_rejected',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => ['donation_id' => $donation->id],
        ]);

        return back()->with('success', __('Donation rejected.'));
    }

    public function analytics(Request $request): Response
    {
        $range  = $request->get('range', 'year');
        $fromDate = match ($range) {
            'month'  => now()->startOfMonth(),
            'quarter'=> now()->subMonths(3)->startOfDay(),
            'year'   => now()->subYear()->startOfDay(),
            default  => now()->subYears(10)->startOfDay(),
        };

        // Monthly revenue — last 12 months
        $monthlyRevenue = Donation::where('status', 'completed')
            ->where('created_at', '>=', now()->subYear()->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount_usd) as revenue, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'revenue' => (float) $r->revenue, 'count' => (int) $r->count]);

        // By platform
        $byPlatform = Donation::where('status', 'completed')
            ->where('created_at', '>=', $fromDate)
            ->selectRaw('platform, COUNT(*) as count, SUM(amount_usd) as revenue')
            ->groupBy('platform')
            ->get()
            ->map(fn ($r) => ['platform' => $r->platform, 'count' => (int) $r->count, 'revenue' => (float) $r->revenue]);

        // By package
        $byPackage = Donation::where('status', 'completed')
            ->where('created_at', '>=', $fromDate)
            ->selectRaw('package_id, COUNT(*) as count, SUM(amount_usd) as revenue')
            ->groupBy('package_id')
            ->with('package:id,title')
            ->get()
            ->map(fn ($r) => [
                'title'   => $r->package?->title ?? __('Manual / Custom'),
                'count'   => (int) $r->count,
                'revenue' => (float) $r->revenue,
            ])
            ->sortByDesc('count')
            ->values();

        // Status breakdown (all time)
        $byStatus = Donation::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Top 10 donors
        $topDonors = Donation::where('status', 'completed')
            ->selectRaw('user_id, SUM(amount_usd) as total_usd, SUM(cashpoints_awarded) as total_cp, MAX(created_at) as last_donation')
            ->groupBy('user_id')
            ->orderByDesc('total_usd')
            ->limit(10)
            ->with('user:id,name,email')
            ->get()
            ->map(fn ($r) => [
                'user_name'      => $r->user?->name ?? '—',
                'total_usd'      => (float) $r->total_usd,
                'total_cp'       => (int) $r->total_cp,
                'last_donation'  => \Carbon\Carbon::parse($r->last_donation)->diffForHumans(),
            ]);

        // KPI cards
        $currentMonth = now()->startOfMonth();
        $prevMonth    = now()->subMonth()->startOfMonth();
        $prevMonthEnd = now()->subMonth()->endOfMonth();

        $revThis = (float) Donation::completed()->where('created_at', '>=', $currentMonth)->sum('amount_usd');
        $revPrev = (float) Donation::completed()->whereBetween('created_at', [$prevMonth, $prevMonthEnd])->sum('amount_usd');

        $countThis = Donation::completed()->where('created_at', '>=', $currentMonth)->count();
        $countPrev = Donation::completed()->whereBetween('created_at', [$prevMonth, $prevMonthEnd])->count();

        $kpis = [
            'revenue_this_month'  => $revThis,
            'revenue_prev_month'  => $revPrev,
            'revenue_change_pct'  => $revPrev > 0 ? round(($revThis - $revPrev) / $revPrev * 100, 1) : null,
            'total_cp_awarded'    => (int) Donation::completed()->sum('cashpoints_awarded'),
            'completed_this_month'=> $countThis,
            'completed_prev_month'=> $countPrev,
            'avg_donation'        => round((float) Donation::completed()->avg('amount_usd'), 2),
        ];

        return Inertia::render('Admin/Donations/Analytics', [
            'kpis'          => $kpis,
            'monthlyRevenue'=> $monthlyRevenue,
            'byPlatform'    => $byPlatform,
            'byPackage'     => $byPackage,
            'byStatus'      => $byStatus,
            'topDonors'     => $topDonors,
            'range'         => $range,
        ]);
    }
}
