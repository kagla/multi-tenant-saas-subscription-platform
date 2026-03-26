<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\InicisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class InicisController extends Controller
{
    public function __construct(
        protected InicisService $inicis
    ) {}

    // ─── 일반결제: 결제창 호출 ───

    public function checkout(Request $request): View
    {
        $request->validate([
            'plan' => ['required', 'in:pro,enterprise'],
        ]);

        $tenant = tenant();
        $plan = $request->input('plan');
        $planConfig = config("plans.{$plan}");
        $user = auth()->user();

        $paymentParams = $this->inicis->preparePayment([
            'goodname' => "SaaS {$planConfig['name']} 플랜 구독",
            'price' => $planConfig['price_krw'],
            'buyername' => $user->name,
            'buyeremail' => $user->email,
            'returnUrl' => route('tenant.inicis.return', ['tenant' => $tenant->subdomain]),
            'closeUrl' => route('tenant.inicis.close', ['tenant' => $tenant->subdomain]),
            'merchantData' => json_encode([
                'tenant_id' => $tenant->id,
                'plan' => $plan,
                'type' => 'subscription',
            ]),
        ]);

        return view('tenant.payment.inicis-checkout', [
            'paymentParams' => $paymentParams,
            'tenant' => $tenant,
            'plan' => $plan,
            'planConfig' => $planConfig,
        ]);
    }

    // ─── 일반결제: 결과 수신 (returnUrl) ───

    public function return(Request $request)
    {
        $tenant = tenant();

        // 인증 실패
        if ($request->input('resultCode') !== '0000') {
            return redirect()->route('tenant.subscription.plans', ['tenant' => $tenant->subdomain])
                ->withErrors(['payment' => '결제 인증 실패: ' . ($request->input('resultMsg') ?? '알 수 없는 오류')]);
        }

        // 2단계: 승인 요청
        $authToken = $request->input('authToken');
        $authUrl = $request->input('authUrl');
        $timestamp = $request->input('timestamp');
        $mid = $request->input('mid');
        $signature = $this->inicis->verifySignature($authToken, $timestamp);

        $result = $this->inicis->approvePayment($authToken, $authUrl, $timestamp, $signature, $mid);

        if (($result['resultCode'] ?? '') !== '0000') {
            return redirect()->route('tenant.subscription.plans', ['tenant' => $tenant->subdomain])
                ->withErrors(['payment' => '결제 승인 실패: ' . ($result['resultMsg'] ?? '알 수 없는 오류')]);
        }

        // 승인 성공 → 구독 처리
        $merchantData = json_decode($request->input('merchantData') ?: ($result['merchantData'] ?? '{}'), true);
        $plan = $merchantData['plan'] ?? 'pro';

        $tenant->update(['plan' => $plan]);

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id, 'stripe_id' => $result['tid'] ?? null],
            [
                'stripe_status' => 'active',
                'stripe_plan' => "inicis_{$plan}",
                'quantity' => 1,
                'trial_ends_at' => null,
            ]
        );

        return redirect()->route('tenant.subscription.success', ['tenant' => $tenant->subdomain])
            ->with('inicis_result', $result);
    }

    // ─── 정기결제: 빌링키 발급 결제창 ───

    public function billingCheckout(Request $request): View
    {
        $request->validate([
            'plan' => ['required', 'in:pro,enterprise'],
        ]);

        $tenant = tenant();
        $plan = $request->input('plan');
        $planConfig = config("plans.{$plan}");
        $user = auth()->user();

        $paymentParams = $this->inicis->prepareBilling([
            'goodname' => "SaaS {$planConfig['name']} 플랜 정기구독",
            'price' => $planConfig['price_krw'],
            'buyername' => $user->name,
            'buyeremail' => $user->email,
            'returnUrl' => route('tenant.inicis.billing.return', ['tenant' => $tenant->subdomain]),
            'closeUrl' => route('tenant.inicis.close', ['tenant' => $tenant->subdomain]),
            'merchantData' => json_encode([
                'tenant_id' => $tenant->id,
                'plan' => $plan,
                'type' => 'billing',
            ]),
        ]);

        return view('tenant.payment.inicis-checkout', [
            'paymentParams' => $paymentParams,
            'tenant' => $tenant,
            'plan' => $plan,
            'planConfig' => $planConfig,
            'isBilling' => true,
        ]);
    }

    // ─── 정기결제: 빌링키 발급 결과 ───

    public function billingReturn(Request $request)
    {
        $tenant = tenant();

        if ($request->input('resultCode') !== '0000') {
            return redirect()->route('tenant.subscription.plans', ['tenant' => $tenant->subdomain])
                ->withErrors(['payment' => '빌링키 발급 실패: ' . ($request->input('resultMsg') ?? '알 수 없는 오류')]);
        }

        // 승인 요청
        $authToken = $request->input('authToken');
        $authUrl = $request->input('authUrl');
        $timestamp = $request->input('timestamp');
        $mid = $request->input('mid');
        $signature = $this->inicis->verifySignature($authToken, $timestamp);

        $result = $this->inicis->approvePayment($authToken, $authUrl, $timestamp, $signature, $mid);

        if (($result['resultCode'] ?? '') !== '0000') {
            return redirect()->route('tenant.subscription.plans', ['tenant' => $tenant->subdomain])
                ->withErrors(['payment' => '빌링키 승인 실패: ' . ($result['resultMsg'] ?? '알 수 없는 오류')]);
        }

        $merchantData = json_decode($request->input('merchantData') ?: ($result['merchantData'] ?? '{}'), true);
        $plan = $merchantData['plan'] ?? 'pro';
        $billKey = $result['CARD_BillKey'] ?? $result['billKey'] ?? null;

        $tenant->update(['plan' => $plan]);

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id, 'stripe_id' => $result['tid'] ?? null],
            [
                'stripe_status' => 'active',
                'stripe_plan' => "inicis_billing_{$plan}",
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]
        );

        // 빌링키 저장 (다음 정기결제에 사용)
        if ($billKey) {
            $tenant->update(['billing_key' => $billKey]);
        }

        Log::info("이니시스 빌링키 발급 성공: tenant={$tenant->id}, plan={$plan}, billKey=" . ($billKey ? 'yes' : 'no'));

        return redirect()->route('tenant.subscription.success', ['tenant' => $tenant->subdomain]);
    }

    // ─── 결제 취소 ───

    public function cancelPayment(Request $request)
    {
        $tenant = tenant();
        $subscription = $tenant->activeSubscription;

        if (! $subscription || ! $subscription->stripe_id) {
            return back()->withErrors(['payment' => '취소할 구독이 없습니다.']);
        }

        $result = $this->inicis->cancelPayment($subscription->stripe_id, '사용자 구독 취소');

        if (($result['resultCode'] ?? '') === '0000') {
            $subscription->update([
                'stripe_status' => 'canceled',
                'ends_at' => now()->endOfMonth(),
            ]);
            $tenant->update([
                'subscription_ends_at' => now()->endOfMonth(),
            ]);

            return back()->with('status', 'subscription-cancelled');
        }

        // 이니시스 취소 실패 시에도 내부적으로 취소 처리 (다음 빌링 중단)
        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => now()->endOfMonth(),
        ]);
        $tenant->update([
            'subscription_ends_at' => now()->endOfMonth(),
        ]);

        return back()->with('status', 'subscription-cancelled');
    }

    // ─── 결제창 닫기 (closeUrl) ───

    public function close(): View
    {
        return view('tenant.payment.inicis-close');
    }

    // ─── 이니시스 노티 수신 (noti_url, 서버간 통신) ───

    public function notify(Request $request)
    {
        Log::info('이니시스 노티 수신', $request->all());

        $resultCode = $request->input('resultCode');
        $tid = $request->input('tid');
        $oid = $request->input('moid') ?? $request->input('oid');

        if ($resultCode === '0000' && $tid) {
            $subscription = Subscription::where('stripe_id', $tid)->first();
            if ($subscription) {
                $subscription->update(['stripe_status' => 'active']);
                Log::info("이니시스 노티: 구독 활성 확인 tid={$tid}");
            }
        }

        return response('OK', 200);
    }
}
