<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InicisService
{
    protected string $mid;
    protected string $signKey;
    protected string $apiKey;
    protected string $iv;
    protected string $billingMid;
    protected string $apiUrl;

    public function __construct()
    {
        $this->mid = config('services.inicis.mid');
        $this->signKey = config('services.inicis.signkey');
        $this->apiKey = config('services.inicis.api_key');
        $this->iv = config('services.inicis.iv');
        $this->billingMid = config('services.inicis.billing_mid');
        $this->apiUrl = config('services.inicis.api_url');
    }

    // ─── 표준결제창 (INIStd) 파라미터 생성 ───

    public function preparePayment(array $params): array
    {
        $timestamp = now()->format('YmdHis');
        $oid = $params['oid'] ?? 'ORDER_' . $this->mid . '_' . $timestamp . '_' . mt_rand(1000, 9999);
        $price = (int) $params['price'];
        $mKey = hash('sha256', $this->signKey);
        $signature = hash('sha256', "oid={$oid}&price={$price}&timestamp={$timestamp}");

        return [
            'version' => '1.0',
            'mid' => $this->mid,
            'oid' => $oid,
            'goodname' => $params['goodname'],
            'price' => $price,
            'currency' => 'WON',
            'buyername' => $params['buyername'] ?? '',
            'buyertel' => $params['buyertel'] ?? '010-0000-0000',
            'buyeremail' => $params['buyeremail'] ?? '',
            'timestamp' => $timestamp,
            'signature' => $signature,
            'mKey' => $mKey,
            'returnUrl' => $params['returnUrl'],
            'closeUrl' => $params['closeUrl'] ?? $params['returnUrl'],
            'popupUrl' => $params['popupUrl'] ?? '',
            'gopaymethod' => $params['gopaymethod'] ?? 'Card',
            // 추가 파라미터
            'acceptmethod' => $params['acceptmethod'] ?? 'below1000:HPP(1)',
            'merchantData' => $params['merchantData'] ?? '',
        ];
    }

    // ─── 결제 승인 (표준결제 2단계) ───

    public function approvePayment(string $authToken, string $authUrl, string $timestamp, string $signature, string $mid): array
    {
        $netCancelUrl = 'https://iniapi.inicis.com/api/v1/extra/net-cancel';
        $charSet = 'UTF-8';

        $response = Http::asForm()->post($authUrl, [
            'mid' => $mid ?: $this->mid,
            'authToken' => $authToken,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'charset' => $charSet,
            'format' => 'JSON',
        ]);

        $result = $response->json();

        if (! $result) {
            Log::error('이니시스 결제 승인 실패: 응답 없음');
            return ['resultCode' => '9999', 'resultMsg' => '결제 승인 요청 실패'];
        }

        Log::info('이니시스 결제 승인 결과', $result);

        return $result;
    }

    // ─── 빌링키 발급용 파라미터 (정기결제) ───

    public function prepareBilling(array $params): array
    {
        $timestamp = now()->format('YmdHis');
        $oid = $params['oid'] ?? 'BILL_' . $this->billingMid . '_' . $timestamp . '_' . mt_rand(1000, 9999);
        $price = (int) $params['price'];
        $mKey = hash('sha256', $this->signKey);
        $signature = hash('sha256', "oid={$oid}&price={$price}&timestamp={$timestamp}");

        return [
            'version' => '1.0',
            'mid' => $this->billingMid,
            'oid' => $oid,
            'goodname' => $params['goodname'],
            'price' => $price,
            'currency' => 'WON',
            'buyername' => $params['buyername'] ?? '',
            'buyertel' => $params['buyertel'] ?? '010-0000-0000',
            'buyeremail' => $params['buyeremail'] ?? '',
            'timestamp' => $timestamp,
            'signature' => $signature,
            'mKey' => $mKey,
            'returnUrl' => $params['returnUrl'],
            'closeUrl' => $params['closeUrl'] ?? $params['returnUrl'],
            'gopaymethod' => 'Card',
            'acceptmethod' => 'billauth(card):below1000',
            'merchantData' => $params['merchantData'] ?? '',
        ];
    }

    // ─── 빌링키로 정기결제 실행 ───

    public function billPayment(string $billKey, array $params): array
    {
        $timestamp = now()->format('YmdHis');
        $oid = $params['oid'] ?? 'REBILL_' . $this->billingMid . '_' . $timestamp;
        $price = (int) $params['price'];

        $hashData = hash('sha512', $this->apiKey . $timestamp . $oid . $price . $this->billingMid);

        $response = Http::asForm()->post($this->apiUrl . '/api/v1/billing', [
            'type' => 'Billing',
            'paymethod' => 'Card',
            'timestamp' => $timestamp,
            'clientIp' => request()->ip(),
            'mid' => $this->billingMid,
            'url' => config('app.url'),
            'moid' => $oid,
            'goodName' => $params['goodname'],
            'price' => $price,
            'billKey' => $billKey,
            'buyerName' => $params['buyername'] ?? '',
            'buyerEmail' => $params['buyeremail'] ?? '',
            'hashData' => $hashData,
        ]);

        $result = $response->json();
        Log::info('이니시스 빌링 결제 결과', $result ?? []);

        return $result ?? ['resultCode' => '9999', 'resultMsg' => '빌링 결제 요청 실패'];
    }

    // ─── 결제 취소 ───

    public function cancelPayment(string $tid, string $msg = '관리자 취소'): array
    {
        $timestamp = now()->format('YmdHis');
        $hashData = hash('sha512', $this->apiKey . 'Refund' . $timestamp . $tid . $this->mid);

        $response = Http::asForm()->post($this->apiUrl . '/api/v1/refund', [
            'type' => 'Refund',
            'paymethod' => 'Card',
            'timestamp' => $timestamp,
            'clientIp' => request()->ip(),
            'mid' => $this->mid,
            'tid' => $tid,
            'msg' => $msg,
            'hashData' => $hashData,
        ]);

        $result = $response->json();
        Log::info('이니시스 결제 취소 결과', $result ?? []);

        return $result ?? ['resultCode' => '9999', 'resultMsg' => '결제 취소 요청 실패'];
    }

    // ─── Signature 검증 ───

    public function verifySignature(string $authToken, string $timestamp): string
    {
        return hash('sha256', "authToken={$authToken}&timestamp={$timestamp}");
    }
}
