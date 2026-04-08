<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class OrderRulesController extends Controller
{
    public function index()
    {
        $cfg = config('order_rules.large_size_protection');

        return response()->json([
            'large_size_protection' => [
                'threshold'        => $cfg['threshold'],
                'surcharge'        => $cfg['surcharge'],
                'large_size_codes' => $cfg['large_size_codes'],
            ],
        ]);
    }
}
