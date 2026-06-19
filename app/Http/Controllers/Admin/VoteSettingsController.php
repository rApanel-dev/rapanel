<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoteSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/VoteSettings', [
            'settings' => [
                'vote_cash_from' => (int) SiteSetting::getValue('vote_cash_from', 10),
                'vote_cash_to'   => (int) SiteSetting::getValue('vote_cash_to', 100),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vote_cash_from' => 'required|integer|min:1',
            'vote_cash_to'   => 'required|integer|min:1',
        ]);

        SiteSetting::setMany($data);

        return back()->with('success', __('Vote settings saved.'));
    }
}
