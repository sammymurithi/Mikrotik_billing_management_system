<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;
    protected $passkey;
    protected $shortcode;
    protected $env;

    public function __construct()
    {
        $this->env = config('services.mpesa.env');
        $this->baseUrl = $this->env === 'sandbox' 
            ? 'https://sandbox.safaricom.co.ke' 
            : 'https://api.safaricom.co.ke';
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->passkey = config('services.mpesa.passkey');
        $this->shortcode = config('services.mpesa.shortcode');
    }

    public function initiateSTKPush($phoneNumber, $amount, $reference)
    {
        try {
            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $response = Http::withToken($this->getAccessToken())
                ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $this->shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PartyA' => $phoneNumber,
                    'PartyB' => $this->shortcode,
                    'PhoneNumber' => $phoneNumber,
                    'CallBackURL' => route('mpesa.callback'),
                    'AccountReference' => $reference,
                    'TransactionDesc' => 'Payment for services'
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push Error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function getAccessToken()
    {
        try {
            $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials
            ])->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

            return $response->json()['access_token'];
        } catch (\Exception $e) {
            Log::error('M-Pesa Access Token Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function validateCallback($data)
    {
        // Implement callback validation logic
        return true;
    }
} 