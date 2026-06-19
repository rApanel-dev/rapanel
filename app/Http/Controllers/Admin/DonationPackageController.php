<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DonationPackageController extends Controller
{
    public function index(): Response
    {
        $packages = DonationPackage::orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($p) => [
                'id'               => $p->id,
                'title'            => $p->title,
                'description'      => $p->description,
                'image_url'        => $p->image_url,
                'price_usd'        => (float) $p->price_usd,
                'cashpoints'       => $p->cashpoints,
                'bonus_percent'    => $p->bonus_percent,
                'total_cashpoints' => $p->totalCashpoints(),
                'is_active'        => $p->is_active,
                'sort_order'       => $p->sort_order,
                'border_color'     => $p->border_color,
                'is_featured'      => $p->is_featured,
            ]);

        return Inertia::render('Admin/DonationPackages/Index', [
            'packages' => $packages,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/DonationPackages/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title'         => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'image'         => 'nullable|image|max:2048',
            'price_usd'     => 'required|numeric|min:0.01|max:9999.99',
            'cashpoints'    => 'required|integer|min:1',
            'bonus_percent' => 'required|integer|min:0|max:100',
            'is_active'     => 'boolean',
            'is_featured'   => 'boolean',
            'sort_order'    => 'nullable|integer|min:0',
            'border_color'  => 'nullable|string|in:blue,gold,success,purple,danger,navy',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('donations/packages', 'public');
        }

        DonationPackage::create([
            'title'         => $request->title,
            'description'   => $request->description,
            'image_path'    => $imagePath,
            'price_usd'     => $request->price_usd,
            'cashpoints'    => $request->cashpoints,
            'bonus_percent' => $request->bonus_percent ?? 0,
            'is_active'     => (bool) $request->is_active,
            'is_featured'   => (bool) $request->is_featured,
            'sort_order'    => $request->sort_order ?? 0,
            'border_color'  => $request->border_color ?? 'blue',
        ]);

        return redirect()->route('admin.donation-packages.index')
            ->with('success', __('Donation package created successfully.'));
    }

    public function edit(DonationPackage $donationPackage): Response
    {
        return Inertia::render('Admin/DonationPackages/Edit', [
            'package' => array_merge($donationPackage->toArray(), [
                'image_url'    => $donationPackage->image_url,
                'price_usd'    => (float) $donationPackage->price_usd,
                'border_color' => $donationPackage->border_color ?? 'blue',
                'is_featured'  => (bool) $donationPackage->is_featured,
            ]),
        ]);
    }

    public function update(Request $request, DonationPackage $donationPackage): RedirectResponse
    {
        $request->validate([
            'title'         => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'image'         => 'nullable|image|max:2048',
            'price_usd'     => 'required|numeric|min:0.01|max:9999.99',
            'cashpoints'    => 'required|integer|min:1',
            'bonus_percent' => 'required|integer|min:0|max:100',
            'is_active'     => 'boolean',
            'is_featured'   => 'boolean',
            'sort_order'    => 'nullable|integer|min:0',
            'border_color'  => 'nullable|string|in:blue,gold,success,purple,danger,navy',
        ]);

        if ($request->hasFile('image')) {
            if ($donationPackage->image_path) {
                Storage::disk('public')->delete($donationPackage->image_path);
            }
            $donationPackage->image_path = $request->file('image')->store('donations/packages', 'public');
        }

        $donationPackage->title         = $request->title;
        $donationPackage->description   = $request->description;
        $donationPackage->price_usd     = $request->price_usd;
        $donationPackage->cashpoints    = $request->cashpoints;
        $donationPackage->bonus_percent = $request->bonus_percent ?? 0;
        $donationPackage->is_active     = (bool) $request->is_active;
        $donationPackage->is_featured   = (bool) $request->is_featured;
        $donationPackage->sort_order    = $request->sort_order ?? 0;
        $donationPackage->border_color  = $request->border_color ?? 'blue';
        $donationPackage->save();

        return redirect()->route('admin.donation-packages.index')
            ->with('success', __('Donation package updated successfully.'));
    }

    public function destroy(DonationPackage $donationPackage): RedirectResponse
    {
        if ($donationPackage->image_path) {
            Storage::disk('public')->delete($donationPackage->image_path);
        }
        $donationPackage->delete();

        return redirect()->route('admin.donation-packages.index')
            ->with('success', __('Donation package deleted successfully.'));
    }

    public function toggle(DonationPackage $donationPackage): RedirectResponse
    {
        $donationPackage->update(['is_active' => ! $donationPackage->is_active]);

        return back()->with('success', $donationPackage->is_active
            ? __('Package activated.')
            : __('Package deactivated.')
        );
    }

    public function toggleFeatured(DonationPackage $donationPackage): RedirectResponse
    {
        $donationPackage->update(['is_featured' => ! $donationPackage->is_featured]);

        return back()->with('success', $donationPackage->is_featured
            ? __('Package marked as featured.')
            : __('Package unmarked as featured.')
        );
    }
}
