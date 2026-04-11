<?php

namespace App\Http\Controllers;

use BaironBernal\ColombiaLocations\Models\Departamento;
use BaironBernal\ColombiaLocations\Models\Municipio;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function departments(): JsonResponse
    {
        $departments = Departamento::orderBy('nombre')->get(['id', 'nombre']);

        return response()->json($departments);
    }

    public function municipalities(int $departmentId): JsonResponse
    {
        $municipalities = Municipio::where('departamento_id', $departmentId)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($municipalities);
    }
}
