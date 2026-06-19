<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class DonationSettingsController extends Controller
{
    /** Keys editable from the admin panel (only DONATION_* vars). */
    private const EDITABLE_KEYS = [
        'DONATION_PAYPAL_ENABLED',
        'DONATION_PAYPAL_CLIENT_ID',
        'DONATION_PAYPAL_SECRET',
        'DONATION_PAYPAL_SANDBOX',
        'DONATION_STRIPE_ENABLED',
        'DONATION_STRIPE_PUBLIC_KEY',
        'DONATION_STRIPE_SECRET_KEY',
        'DONATION_STRIPE_WEBHOOK_SECRET',
    ];

    public function index(): Response
    {
        $settings = [
            'paypal_enabled'        => config('services.donations.paypal_enabled'),
            'paypal_client_id'      => $this->masked(config('services.donations.paypal_client_id')),
            'paypal_secret'         => $this->masked(config('services.donations.paypal_secret')),
            'paypal_sandbox'        => config('services.donations.paypal_sandbox'),
            'stripe_enabled'        => config('services.donations.stripe_enabled'),
            'stripe_public_key'     => config('services.donations.stripe_public_key'),
            'stripe_secret_key'     => $this->masked(config('services.donations.stripe_secret_key')),
            'stripe_webhook_secret' => $this->masked(config('services.donations.stripe_webhook_secret')),
        ];

        return Inertia::render('Admin/DonationSettings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'paypal_enabled'        => 'boolean',
            'paypal_client_id'      => 'nullable|string|max:255',
            'paypal_secret'         => 'nullable|string|max:255',
            'paypal_sandbox'        => 'boolean',
            'stripe_enabled'        => 'boolean',
            'stripe_public_key'     => 'nullable|string|max:255',
            'stripe_secret_key'     => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
        ]);

        $map = [
            'DONATION_PAYPAL_ENABLED'        => $request->boolean('paypal_enabled') ? 'true' : 'false',
            'DONATION_PAYPAL_SANDBOX'         => $request->boolean('paypal_sandbox') ? 'true' : 'false',
            'DONATION_STRIPE_ENABLED'         => $request->boolean('stripe_enabled') ? 'true' : 'false',
        ];

        // Only update secrets when provided and not the masked placeholder
        foreach ([
            'DONATION_PAYPAL_CLIENT_ID'      => $request->paypal_client_id,
            'DONATION_PAYPAL_SECRET'         => $request->paypal_secret,
            'DONATION_STRIPE_PUBLIC_KEY'     => $request->stripe_public_key,
            'DONATION_STRIPE_SECRET_KEY'     => $request->stripe_secret_key,
            'DONATION_STRIPE_WEBHOOK_SECRET' => $request->stripe_webhook_secret,
        ] as $envKey => $value) {
            if ($value !== null && $value !== '********') {
                $map[$envKey] = $value;
            }
        }

        $this->writeEnv($map);

        Artisan::call('config:clear');

        return back()->with('success', __('Donation settings updated.'));
    }

    private function masked(?string $value): ?string
    {
        if (empty($value)) return null;
        return '********';
    }

    private function writeEnv(array $data): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);

        foreach ($data as $key => $value) {
            if (!in_array($key, self::EDITABLE_KEYS, true)) {
                continue;
            }

            $escapedValue = str_contains($value, ' ') ? "\"{$value}\"" : $value;

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
            } else {
                $content .= "\n{$key}={$escapedValue}";
            }
        }

        file_put_contents($path, $content);
    }
}
