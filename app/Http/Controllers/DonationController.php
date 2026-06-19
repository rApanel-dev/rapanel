<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Donation;
use App\Models\DonationPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;
use Stripe\Checkout\Session as StripeSession;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

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

        return Inertia::render('Donations/Index', [
            'packages'       => $packages,
            'paypalEnabled'  => (bool) config('services.donations.paypal_enabled'),
            'stripeEnabled'  => (bool) config('services.donations.stripe_enabled'),
            'paypalClientId' => config('services.donations.paypal_client_id'),
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
                    'description' => "{$package->title} — {$package->totalCashpoints()} CashPoints",
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
                            'description' => "{$package->totalCashpoints()} CashPoints",
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

    // ─── Success / Cancel ────────────────────────────────────────────────────

    public function success(Request $request): Response
    {
        $sessionId = $request->query('session_id');
        $donation  = $sessionId
            ? Donation::where('transaction_id', $sessionId)->first()
            : null;

        return Inertia::render('Donations/Success', [
            'cashpoints' => $donation?->cashpoints_awarded,
        ]);
    }

    public function cancel(): Response
    {
        return Inertia::render('Donations/Cancel');
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
