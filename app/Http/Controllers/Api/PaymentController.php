<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        $amount = $request->input('amount');
        $currency = 'COP';
        $reference = 'MEDINCAMISAS-' . uniqid();

        $signature = hash(
            'sha256',
            $reference . $amount * 100 . $currency . env('WOMPI_INTEGRITY_KEY')
        );

        return view('checkout', [
            'public_key' => env('WOMPI_PUBLIC_KEY'),
            'currency' => $currency,
            'amount_in_cents' => $amount * 100,
            'reference' => $reference,
            'signature' => $signature,
        ]);
    }
}
