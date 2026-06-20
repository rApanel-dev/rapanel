<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Donation;
use App\Models\DonationPackage;
use App\Models\GameAccount;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;
use Stripe\Checkout\Session as StripeSession;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

class DonationController extends Controller
{
    public function index(): Response
    {
        $packages = DonationPackage::active()
            ->get()
            ->map(fn ($p) => [
                'id'               => $p->id,
                'title'            => $p->title,
                'description'      => $p->description,
                'image_url'        => $p->image_url,
                'price_usd'        => (float) $p->price_usd,
                'cashpoints'       => $p->cashpoints,
                'bonus_percent'    => $p->bonus_percent,
                'total_cashpoints' => $p->totalCashpoints(),
                'border_color'     => $p->border_color ?? 'blue',
                'border_hex'       => DonationPackage::colorHex($p->border_color ?? 'blue'),
                'is_featured'      => (bool) $p->is_featured,
            ]);

        $user              = Auth::user();
        $cashPointsEnabled = (bool) config('services.ra.cashpoints_enabled');

        $gameAccounts        = collect();
        $onlineAccountIds    = [];
        $cashPointsByAccount = [];

        if ($user) {
            $gameAccounts = $user->gameAccounts()->select('account_id', 'userid')->get();

            if ($gameAccounts->isNotEmpty()) {
                $accountIds = $gameAccounts->pluck('account_id')->toArray();

                $onlineAccountIds = DB::connection('mysql_main')
                    ->table('char')
                    ->whereIn('account_id', $accountIds)
                    ->where('online', '>', 0)
                    ->pluck('account_id')
                    ->unique()
                    ->values()
                    ->toArray();

                // Solo consultar balances de CP si el sistema está habilitado
                if ($cashPointsEnabled) {
                    $cpRows = DB::connection('mysql_main')
                        ->table('acc_reg_num')
                        ->whereIn('account_id', $accountIds)
                        ->where('key', '#CASHPOINTS')
                        ->get(['account_id', 'value']);

                    foreach ($cpRows as $row) {
                        $cashPointsByAccount[$row->account_id] = (int) $row->value;
                    }
                }
            }
        }

        return Inertia::render('Donations/Index', [
            'packages'         => $packages,
            'paypalEnabled'    => (bool) SiteSetting::getValue('donation_paypal_enabled', config('services.donations.paypal_enabled', false)),
            'stripeEnabled'    => (bool) SiteSetting::getValue('donation_stripe_enabled', config('services.donations.stripe_enabled', false)),
            'mpEnabled'        => (bool) SiteSetting::getValue('donation_mp_enabled', config('services.donations.mp_enabled', false)),
            'paypalClientId'   => config('services.donations.paypal_client_id'),
            'donationPoints'   => $user ? (int) $user->donation_points : null,
            'cashPointsEnabled'=> $cashPointsEnabled,
            'gameAccounts'     => $gameAccounts->map(fn ($a) => [
                'account_id'  => $a->account_id,
                'userid'      => $a->userid,
                'cash_points' => $cashPointsByAccount[$a->account_id] ?? 0,
            ])->values(),
            'onlineAccountIds' => $onlineAccountIds,
            'conversionRate'   => [
                'from' => (int) SiteSetting::getValue('donation_cash_from', 10),
                'to'   => (int) SiteSetting::getValue('donation_cash_to', 100),
            ],
        ]);
    }

    // ─── PayPal ──────────────────────────────────────────────────────────────

    public function paypalCreate(Request $request): JsonResponse
    {
        $request->validate(['package_id' => 'required|exists:donation_packages,id']);

        $package = DonationPackage::findOrFail($request->package_id);

        try {
            $provider = $this->paypalProvider();
            $provider->getAccessToken();

            $order = $provider->createOrder([
                'intent'         => 'CAPTURE',
                'purchase_units' => [[
                    'amount'      => [
                        'currency_code' => 'USD',
                        'value'         => number_format((float) $package->price_usd, 2, '.', ''),
                    ],
                    'description' => "{$package->title} — {$package->totalCashpoints()} Donation Points",
                    'custom_id'   => json_encode([
                        'package_id' => $package->id,
                        'user_id'    => Auth::id(),
                    ]),
                ]],
                'application_context' => [
                    'return_url' => route('donations.success'),
                    'cancel_url' => route('donations.cancel'),
                ],
            ]);

            return response()->json(['order_id' => $order['id']]);
        } catch (\Throwable $e) {
            Log::error('PayPal create order error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'PayPal error. Please try again.'], 500);
        }
    }

    public function paypalCapture(Request $request): JsonResponse
    {
        $request->validate([
            'order_id'   => 'required|string',
            'package_id' => 'required|exists:donation_packages,id',
        ]);

        // Idempotency: check if already processed
        if (Donation::where('transaction_id', $request->order_id)->exists()) {
            return response()->json(['success' => true, 'already_processed' => true]);
        }

        $package = DonationPackage::findOrFail($request->package_id);

        try {
            $provider = $this->paypalProvider();
            $provider->getAccessToken();
            $capture = $provider->capturePaymentOrder($request->order_id);

            if (($capture['status'] ?? '') !== 'COMPLETED') {
                return response()->json(['error' => 'Payment not completed.'], 400);
            }

            $cashpoints = $package->totalCashpoints();

            $donation = Donation::create([
                'user_id'           => Auth::id(),
                'package_id'        => $package->id,
                'platform'          => 'paypal',
                'transaction_id'    => $request->order_id,
                'amount_usd'        => $package->price_usd,
                'cashpoints_awarded'=> $cashpoints,
                'status'            => 'pending',
                'metadata'          => $capture,
            ]);

            $donation->markCompleted($cashpoints);

            ActionLog::create([
                'user_id'    => Auth::id(),
                'category'   => 'DONATION',
                'action'     => 'donation_completed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata'   => [
                    'platform'    => 'paypal',
                    'transaction' => $request->order_id,
                    'package'     => $package->title,
                    'amount_usd'  => (float) $package->price_usd,
                    'cashpoints'  => $cashpoints,
                ],
            ]);

            return response()->json([
                'success'    => true,
                'cashpoints' => $cashpoints,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayPal capture error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Capture failed. Please contact support.'], 500);
        }
    }

    public function paypalWebhook(Request $request): HttpResponse
    {
        // Backup webhook — only process PAYMENT.CAPTURE.COMPLETED events
        $event = $request->all();

        if (($event['event_type'] ?? '') !== 'PAYMENT.CAPTURE.COMPLETED') {
            return response('', 200);
        }

        $orderId = $event['resource']['supplementary_data']['related_ids']['order_id'] ?? null;
        if (!$orderId) return response('', 200);

        // Skip if already processed via capture endpoint
        if (Donation::where('transaction_id', $orderId)->where('status', 'completed')->exists()) {
            return response('', 200);
        }

        Log::info('PayPal webhook received', ['order_id' => $orderId, 'event' => $event['event_type']]);

        return response('', 200);
    }

    // ─── Stripe ──────────────────────────────────────────────────────────────

    public function stripeCreate(Request $request): JsonResponse
    {
        $request->validate(['package_id' => 'required|exists:donation_packages,id']);

        $package = DonationPackage::findOrFail($request->package_id);

        try {
            Stripe::setApiKey(config('services.donations.stripe_secret_key'));

            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'usd',
                        'unit_amount'  => (int) round((float) $package->price_usd * 100),
                        'product_data' => [
                            'name'        => $package->title,
                            'description' => "{$package->totalCashpoints()} Donation Points",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => route('donations.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('donations.cancel'),
                'metadata'    => [
                    'package_id' => $package->id,
                    'user_id'    => Auth::id(),
                ],
            ]);

            return response()->json(['checkout_url' => $session->url]);
        } catch (\Throwable $e) {
            Log::error('Stripe create session error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Stripe error. Please try again.'], 500);
        }
    }

    public function stripeWebhook(Request $request): HttpResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.donations.stripe_webhook_secret');

        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature invalid', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response('', 200);
        }

        $session   = $event->data->object;
        $sessionId = $session->id;
        $meta      = $session->metadata;

        // Idempotency
        if (Donation::where('transaction_id', $sessionId)->exists()) {
            return response('', 200);
        }

        $package = DonationPackage::find($meta->package_id ?? null);
        if (!$package) {
            Log::warning('Stripe webhook: package not found', ['session' => $sessionId]);
            return response('', 200);
        }

        $cashpoints = $package->totalCashpoints();

        $donation = Donation::create([
            'user_id'           => $meta->user_id ?? null,
            'package_id'        => $package->id,
            'platform'          => 'stripe',
            'transaction_id'    => $sessionId,
            'amount_usd'        => $session->amount_total / 100,
            'cashpoints_awarded'=> $cashpoints,
            'status'            => 'pending',
            'metadata'          => (array) $session,
        ]);

        $donation->markCompleted($cashpoints);

        if ($meta->user_id ?? null) {
            ActionLog::create([
                'user_id'    => $meta->user_id,
                'category'   => 'DONATION',
                'action'     => 'donation_completed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata'   => [
                    'platform'    => 'stripe',
                    'transaction' => $sessionId,
                    'package'     => $package->title,
                    'amount_usd'  => $session->amount_total / 100,
                    'cashpoints'  => $cashpoints,
                ],
            ]);
        }

        return response('', 200);
    }

    // ─── Convert Donation Points → CashPoints ────────────────────────────────

    public function convert(Request $request): RedirectResponse
    {
        $from = (int) SiteSetting::getValue('donation_cash_from', 10);
        $to   = (int) SiteSetting::getValue('donation_cash_to', 100);

        $validated = $request->validate([
            'donation_points' => ['required', 'integer', 'min:' . $from],
            'account_id'      => ['required', 'integer'],
        ]);

        $user      = Auth::user();
        $dpSpend   = (int) $validated['donation_points'];
        $accountId = (int) $validated['account_id'];

        if ($dpSpend % $from !== 0) {
            return back()->withErrors(['donation_points' => __('Donation points must be a multiple of :n.', ['n' => $from])]);
        }

        if ($user->donation_points < $dpSpend) {
            return back()->withErrors(['donation_points' => __('Insufficient donation points.')]);
        }

        $account = GameAccount::where('account_id', $accountId)
            ->where('master_id', $user->id)
            ->first();

        if (! $account) {
            return back()->withErrors(['account_id' => __('Game account not found.')]);
        }

        $hasOnlineChars = DB::connection('mysql_main')
            ->table('char')
            ->where('account_id', $account->account_id)
            ->where('online', '>', 0)
            ->exists();

        if ($hasOnlineChars) {
            return back()->withErrors(['account_id' => __('You must log out all characters before converting donation points.')]);
        }

        $cashGain = intdiv($dpSpend, $from) * $to;

        DB::transaction(function () use ($user, $account, $dpSpend, $cashGain, $request) {
            $user->decrement('donation_points', $dpSpend);

            DB::connection('mysql_main')->table('acc_reg_num')->updateOrInsert(
                ['account_id' => $account->account_id, 'key' => '#CASHPOINTS'],
                ['value' => DB::raw('value + ' . (int) $cashGain)]
            );

            ActionLog::create([
                'user_id'    => $user->id,
                'category'   => 'DONATION',
                'action'     => 'donation_points_converted',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata'   => [
                    'donation_points_spent' => $dpSpend,
                    'cash_points_gained'    => $cashGain,
                    'account_id'            => $account->account_id,
                    'userid'                => $account->userid,
                ],
            ]);
        });

        return back()->with('success', __('Converted :dp Donation Points into :cp Cash Points for :user.', [
            'dp'   => $dpSpend,
            'cp'   => $cashGain,
            'user' => $account->userid,
        ]));
    }

    // ─── Success / Cancel ────────────────────────────────────────────────────

    public function success(Request $request): Response
    {
        // Stripe
        $sessionId = $request->query('session_id');

        // PayPal redirect fallback (when popup falls back to full-page redirect)
        $paypalToken   = $request->query('token');
        $paypalPayerId = $request->query('PayerID');

        if ($paypalToken && $paypalPayerId && !Donation::where('transaction_id', $paypalToken)->exists()) {
            try {
                $provider = $this->paypalProvider();
                $provider->getAccessToken();
                $capture = $provider->capturePaymentOrder($paypalToken);

                if (($capture['status'] ?? '') === 'COMPLETED') {
                    $customId = $capture['purchase_units'][0]['payments']['captures'][0]['custom_id'] ?? null;
                    $meta     = $customId ? json_decode($customId, true) : null;
                    $package  = DonationPackage::find($meta['package_id'] ?? null);

                    if ($package) {
                        $cashpoints = $package->totalCashpoints();

                        $donation = Donation::create([
                            'user_id'            => $meta['user_id'] ?? null,
                            'package_id'         => $package->id,
                            'platform'           => 'paypal',
                            'transaction_id'     => $paypalToken,
                            'amount_usd'         => $package->price_usd,
                            'cashpoints_awarded' => $cashpoints,
                            'status'             => 'pending',
                            'metadata'           => $capture,
                        ]);

                        $donation->markCompleted($cashpoints);

                        if ($meta['user_id'] ?? null) {
                            ActionLog::create([
                                'user_id'    => $meta['user_id'],
                                'category'   => 'DONATION',
                                'action'     => 'donation_completed',
                                'ip_address' => $request->ip(),
                                'user_agent' => $request->userAgent(),
                                'metadata'   => [
                                    'platform'    => 'paypal',
                                    'transaction' => $paypalToken,
                                    'package'     => $package->title,
                                    'amount_usd'  => (float) $package->price_usd,
                                    'cashpoints'  => $cashpoints,
                                ],
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('PayPal redirect capture error', ['error' => $e->getMessage()]);
            }
        }

        $donation = null;
        if ($sessionId) {
            $donation = Donation::where('transaction_id', $sessionId)->first();
        } elseif ($paypalToken) {
            $donation = Donation::where('transaction_id', $paypalToken)->first();
        }

        return Inertia::render('Donations/Success', [
            'cashpoints' => $donation?->cashpoints_awarded,
        ]);
    }

    public function cancel(): Response
    {
        return Inertia::render('Donations/Cancel');
    }

    // ─── Mercado Pago ────────────────────────────────────────────────────────

    public function mpCreate(Request $request): JsonResponse
    {
        $request->validate(['package_id' => 'required|exists:donation_packages,id']);

        $package = DonationPackage::findOrFail($request->package_id);

        try {
            MercadoPagoConfig::setAccessToken(config('services.donations.mp_access_token'));

            $client = new PreferenceClient();

            $user       = Auth::user();
            $serverName = config('services.ra.server_name', 'rApanel');

            $preferenceData = [
                'items' => [[
                    'id'          => (string) $package->id,
                    'title'       => $package->title,
                    'description' => $package->description ?: "{$package->totalCashpoints()} Donation Points — {$serverName}",
                    'quantity'    => 1,
                    'unit_price'  => (float) $package->price_usd,
                    'currency_id' => 'USD',
                ]],
                'payer' => [
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'back_urls' => [
                    'success' => route('donations.mp.success'),
                    'failure' => route('donations.mp.failure'),
                    'pending' => route('donations.mp.pending'),
                ],
                'auto_return'        => 'approved',
                'external_reference' => json_encode([
                    'package_id' => $package->id,
                    'user_id'    => Auth::id(),
                ]),
                'notification_url'   => route('donations.mp.webhook'),
                'statement_descriptor' => $serverName,
            ];

            if ($package->image_url) {
                $preferenceData['items'][0]['picture_url'] = $package->image_url;
            }

            $preference = $client->create($preferenceData);

            return response()->json(['checkout_url' => $preference->init_point]);
        } catch (\Throwable $e) {
            Log::error('MercadoPago create preference error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'MercadoPago error. Please try again.'], 500);
        }
    }

    public function mpSuccess(Request $request): Response
    {
        $paymentId = $request->query('payment_id');
        $status    = $request->query('collection_status') ?? $request->query('status');
        $extRef    = $request->query('external_reference');

        if ($paymentId && $status === 'approved' && $extRef) {
            // Idempotency
            if (!Donation::where('transaction_id', $paymentId)->exists()) {
                try {
                    MercadoPagoConfig::setAccessToken(config('services.donations.mp_access_token'));
                    $client  = new PaymentClient();
                    $payment = $client->get($paymentId);

                    if ($payment->status === 'approved') {
                        $meta    = json_decode($extRef, true);
                        $package = DonationPackage::find($meta['package_id'] ?? null);

                        if ($package) {
                            $cashpoints = $package->totalCashpoints();

                            $donation = Donation::create([
                                'user_id'            => $meta['user_id'] ?? null,
                                'package_id'         => $package->id,
                                'platform'           => 'mercadopago',
                                'transaction_id'     => $paymentId,
                                'amount_usd'         => $package->price_usd,
                                'cashpoints_awarded' => $cashpoints,
                                'status'             => 'pending',
                                'metadata'           => (array) $payment,
                            ]);

                            $donation->markCompleted($cashpoints);

                            if ($meta['user_id'] ?? null) {
                                ActionLog::create([
                                    'user_id'    => $meta['user_id'],
                                    'category'   => 'DONATION',
                                    'action'     => 'donation_completed',
                                    'ip_address' => $request->ip(),
                                    'user_agent' => $request->userAgent(),
                                    'metadata'   => [
                                        'platform'    => 'mercadopago',
                                        'transaction' => $paymentId,
                                        'package'     => $package->title,
                                        'amount_usd'  => (float) $package->price_usd,
                                        'cashpoints'  => $cashpoints,
                                    ],
                                ]);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('MercadoPago success processing error', ['error' => $e->getMessage()]);
                }
            }

            $donation = Donation::where('transaction_id', $paymentId)->first();
            return Inertia::render('Donations/Success', [
                'cashpoints' => $donation?->cashpoints_awarded,
            ]);
        }

        return Inertia::render('Donations/Success', ['cashpoints' => null]);
    }

    public function mpFailure(Request $request): Response
    {
        return Inertia::render('Donations/Cancel');
    }

    public function mpPending(Request $request): Response
    {
        return Inertia::render('Donations/Success', ['cashpoints' => null]);
    }

    public function mpWebhook(Request $request): HttpResponse
    {
        // Soporta formato IPN legacy (?topic=payment&id=...) y nuevo (?type=payment&data_id=... / body JSON)
        $type = $request->query('type')
             ?? $request->input('type')
             ?? ($request->query('topic') === 'payment' ? 'payment' : null);

        $id = $request->query('data_id')
           ?? $request->input('data.id')
           ?? $request->query('id');

        if ($type !== 'payment' || !$id) {
            return response('', 200);
        }

        // Skip if already processed
        if (Donation::where('transaction_id', $id)->where('status', 'completed')->exists()) {
            return response('', 200);
        }

        try {
            MercadoPagoConfig::setAccessToken(config('services.donations.mp_access_token'));
            $client  = new PaymentClient();
            $payment = $client->get($id);

            if ($payment->status !== 'approved') {
                return response('', 200);
            }

            $extRef  = $payment->external_reference ?? null;
            $meta    = $extRef ? json_decode($extRef, true) : null;
            $package = DonationPackage::find($meta['package_id'] ?? null);

            if (!$package || Donation::where('transaction_id', (string) $id)->exists()) {
                return response('', 200);
            }

            $cashpoints = $package->totalCashpoints();

            $donation = Donation::create([
                'user_id'            => $meta['user_id'] ?? null,
                'package_id'         => $package->id,
                'platform'           => 'mercadopago',
                'transaction_id'     => (string) $id,
                'amount_usd'         => $package->price_usd,
                'cashpoints_awarded' => $cashpoints,
                'status'             => 'pending',
                'metadata'           => (array) $payment,
            ]);

            $donation->markCompleted($cashpoints);

            Log::info('MercadoPago webhook processed', ['payment_id' => $id]);
        } catch (\Throwable $e) {
            Log::error('MercadoPago webhook error', ['error' => $e->getMessage()]);
        }

        return response('', 200);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function paypalProvider(): PayPalClient
    {
        $isSandbox = config('services.donations.paypal_sandbox', true);
        $mode      = $isSandbox ? 'sandbox' : 'live';

        config(['paypal' => [
            'mode'    => $mode,
            $mode     => [
                'client_id' => config('services.donations.paypal_client_id'),
                'client_secret' => config('services.donations.paypal_secret'),
            ],
            'payment_action' => 'Sale',
            'currency'       => 'USD',
            'notify_url'     => route('donations.paypal.webhook'),
            'locale'         => 'en_US',
            'validate_ssl'   => true,
        ]]);

        return new PayPalClient;
    }
}
