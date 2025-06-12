<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    public function getTransactions()
    {
        $transactions = Transaction::with('user')
            ->latest()
            ->paginate(10);

        return Inertia::render('Payments/Index', [
            'transactions' => $transactions
        ]);
    }

    public function getTransactionStats()
    {
        $stats = [
            'total_revenue' => Transaction::where('status', 'completed')->sum('amount'),
            'total_transactions' => Transaction::count(),
            'successful_transactions' => Transaction::where('status', 'completed')->count(),
            'failed_transactions' => Transaction::where('status', 'failed')->count(),
            'pending_transactions' => Transaction::where('status', 'pending')->count(),
        ];

        return Inertia::render('Payments/Index', [
            'stats' => $stats
        ]);
    }

    protected function formatPhoneNumber($phone)
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If number starts with +254, replace with 0
        if (strpos($phone, '254') === 0) {
            $phone = '0' . substr($phone, 3);
        }
        
        // If number starts with 254, replace with 0
        if (strpos($phone, '254') === 0) {
            $phone = '0' . substr($phone, 3);
        }
        
        // Ensure number starts with 0 and has correct length
        if (strlen($phone) === 10 && $phone[0] === '0') {
            return $phone;
        }
        
        throw new \Exception('Invalid phone number format');
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'phone_number' => 'required|string|regex:/^0[0-9]{9}$/'
        ], [
            'phone_number.regex' => 'The phone number must start with 0 followed by 9 digits (e.g., 0797925090)'
        ]);

        try {
            $formattedPhone = $this->formatPhoneNumber($request->phone_number);
            
            $transaction = Transaction::create([
                'transaction_id' => Str::uuid(),
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'payment_method' => 'mpesa',
                'status' => 'pending',
                'mpesa_phone_number' => $formattedPhone
            ]);

            $response = $this->mpesaService->initiateSTKPush(
                $request->amount,
                $formattedPhone,
                $transaction->transaction_id
            );

            if ($response['ResponseCode'] === '0') {
                return back()->with('success', 'STK Push initiated. Please check your phone to complete the payment.');
            } else {
                $transaction->update(['status' => 'failed']);
                return back()->with('error', 'Failed to initiate STK Push. Please try again.');
            }
        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->update(['status' => 'failed']);
            }
            return back()->with('error', $e->getMessage() === 'Invalid phone number format' 
                ? 'Invalid phone number format. Please use format: 0797925090'
                : 'An error occurred while processing your payment. Please try again.');
        }
    }

    public function handleMpesaCallback(Request $request)
    {
        $callbackData = $request->all();
        
        if ($this->mpesaService->validateCallback($callbackData)) {
            $transaction = Transaction::where('transaction_id', $callbackData['MerchantRequestID'])->first();
            
            if ($transaction) {
                $transaction->update([
                    'status' => $callbackData['ResultCode'] === '0' ? 'completed' : 'failed',
                    'mpesa_receipt_number' => $callbackData['ResultCode'] === '0' ? $callbackData['MpesaReceiptNumber'] : null,
                    'mpesa_transaction_date' => $callbackData['ResultCode'] === '0' ? $callbackData['TransactionDate'] : null,
                    'metadata' => $callbackData
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }
} 