<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoteSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoteSiteController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/VoteSites/Index', [
            'sites' => VoteSite::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'icon_url'        => 'nullable|url|max:500',
            'vote_url'        => 'required|string|max:500',
            'callback_type'   => 'required|in:honor,callback',
            'callback_secret' => 'nullable|string|max:255',
            'callback_ip'     => 'nullable|string|max:100',
            'points_per_vote' => 'required|integer|min:1|max:9999',
            'cooldown_hours'  => 'required|integer|min:1|max:168',
            'is_active'       => 'boolean',
            'sort_order'      => 'integer|min:0',
        ]);

        VoteSite::create($data);

        return back()->with('success', __('Vote site created.'));
    }

    public function update(Request $request, VoteSite $voteSite): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'icon_url'        => 'nullable|url|max:500',
            'vote_url'        => 'required|string|max:500',
            'callback_type'   => 'required|in:honor,callback',
            'callback_secret' => 'nullable|string|max:255',
            'callback_ip'     => 'nullable|string|max:100',
            'points_per_vote' => 'required|integer|min:1|max:9999',
            'cooldown_hours'  => 'required|integer|min:1|max:168',
            'is_active'       => 'boolean',
            'sort_order'      => 'integer|min:0',
        ]);

        $voteSite->update($data);

        return back()->with('success', __('Vote site updated.'));
    }

    public function destroy(VoteSite $voteSite): RedirectResponse
    {
        $voteSite->delete();

        return back()->with('success', __('Vote site deleted.'));
    }

    public function toggleActive(VoteSite $voteSite): RedirectResponse
    {
        $voteSite->update(['is_active' => ! $voteSite->is_active]);

        return back()->with('success', $voteSite->is_active ? __('Vote site enabled.') : __('Vote site disabled.'));
    }
}
