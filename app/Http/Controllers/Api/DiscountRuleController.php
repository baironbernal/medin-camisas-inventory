<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountRule;


class DiscountRuleController extends Controller
{
    public function index()
    {
        $rules = DiscountRule::query()
            ->where('is_active', true)
            ->orderBy('min_quantity')
            ->get([
                'id',
                'name',
                'min_quantity',
                'max_quantity',
                'discount_type',
                'discount_value',
            ]);

        return response()->json($rules);
    }
    
}
