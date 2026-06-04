<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeAd;
use Illuminate\Http\JsonResponse;

class HomeAdController extends Controller
{
    public function index(): JsonResponse
    {
        $ads = HomeAd::active()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'message']);

        return response()->json(['success' => true, 'data' => $ads]);
    }
}
