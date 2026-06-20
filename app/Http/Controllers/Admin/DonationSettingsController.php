<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DonationSettingsController extends Controller
{
    public function index(): Response
    {
        $settings = [
            'paypal_enabled'     => (bool) SiteSetting::getValue('donation_paypal_enabled',
                                        config('services.donations.paypal_enabled', false)),
            'stripe_enabled'     => (bool) SiteSetting::getValue('donation_stripe_enabled',
                                        config('services.donations.stripe_enabled', false)),
            'donation_cash_from' => (int) SiteSetting::getValue('donation_cash_from', 10),
            'donation_cash_to'   => (int) SiteSetting::getValue('donation_cash_to', 100),
            'monthly_cost'       => (float) SiteSetting::getValue('donation_monthly_cost', 0),
            'monthly_goal'       => (float) SiteSetting::getValue('donation_monthly_goal', 0),
            // Info de solo lectura (viene de .env, no editable desde el panel)
            'paypal_configured'  => ! empty(config('services.donations.paypal_client_id')),
            'stripe_configured'  => ! empty(config('services.donations.stripe_secret_key')),
            'stripe_webhook_url' => url('/donations/stripe/webhook'),
            'mp_enabled'         => (bool) SiteSetting::getValue('donation_mp_enabled',
                                        config('services.donations.mp_enabled', false)),
            'mp_configured'      => ! empty(config('services.donations.mp_access_token')),
            'mp_webhook_url'     => url('/donations/mp/webhook'),
        ];

        return Inertia::render('Admin/DonationSettings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'paypal_enabled'     => 'boolean',
            'stripe_enabled'     => 'boolean',
            'mp_enabled'         => 'boolean',
            'donation_cash_from' => 'required|integer|min:1',
            'donation_cash_to'   => 'required|integer|min:1',
            'monthly_cost'       => 'numeric|min:0|max:9999.99',
            'monthly_goal'       => 'numeric|min:0|max:9999.99',
        ]);

        SiteSetting::setMany([
            'donation_paypal_enabled' => $request->boolean('paypal_enabled') ? '1' : '0',
            'donation_stripe_enabled' => $request->boolean('stripe_enabled') ? '1' : '0',
            'donation_mp_enabled'     => $request->boolean('mp_enabled') ? '1' : '0',
            'donation_cash_from'      => $request->integer('donation_cash_from'),
            'donation_cash_to'        => $request->integer('donation_cash_to'),
            'donation_monthly_cost'   => $request->input('monthly_cost', 0),
            'donation_monthly_goal'   => $request->input('monthly_goal', 0),
        ]);

        return back()->with('success', __('Donation settings updated.'));
    }
}
