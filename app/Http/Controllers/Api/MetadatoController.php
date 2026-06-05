<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Metadato;
use Illuminate\Http\JsonResponse;

class MetadatoController extends Controller
{
    public function bannerVideo(): JsonResponse
    {
        $valor = Metadato::get('home_banner_video');

        $url = $valor
            ? url('storage/' . $valor)
            : null;

        return response()->json(['success' => true, 'data' => ['url' => $url]]);
    }
}
