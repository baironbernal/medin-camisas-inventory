<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sizeAttribute = Attribute::where('code', 'SIZE')->first();

        if (!$sizeAttribute) {
            return;
        }

    $sizes = [
        ['code' => 'UNICA', 'value' => 'Única'], 
        ['code' => 'DOS', 'value' => '2'], 
        ['code' => 'CUATRO', 'value' => '4'], 
        ['code' => 'SEIS', 'value' => '6'], 
        ['code' => 'OCHO', 'value' => '8'], 
        ['code' => 'DIEZ', 'value' => '10'], 
        ['code' => 'DOCE', 'value' => '12'], 
        ['code' => 'CATORCE', 'value' => '14'], 
        ['code' => 'DIECISEIS', 'value' => '16'], 
        ['code' => 'DIECIOCHO', 'value' => '18'], 
        ['code' => 'VEINTE', 'value' => '20'],
        ['code' => 'VEINTIDOS', 'value' => '22'],
        ['code' => 'VEINTICUATRO', 'value' => '24'],
        ['code' => 'VEINTISEIS', 'value' => '26'],
        ['code' => 'VEINTIOCHO', 'value' => '28'],
        ['code' => 'TREINTA', 'value' => '30'],
        ['code' => 'TREINTA-Y-DOS', 'value' => '32'],
        ['code' => 'TREINTA-Y-CUATRO', 'value' => '34'],
        ['code' => 'TREINTA-Y-SEIS', 'value' => '36'],
        ['code' => 'TREINTA-Y-OCHO', 'value' => '38'],
        ['code' => 'CUARENTA', 'value' => '40'],
        ['code' => 'CUARENTA-Y-DOS', 'value' => '42'],
        ['code' => 'CUARENTA Y CUATRO', 'value' => '44'],
       
    ];

    foreach ($sizes as $index => $sizeData) { // Cambié el nombre a $sizeData para mayor claridad
        AttributeValue::updateOrCreate(
            [
                'attribute_id' => $sizeAttribute->id,
                'code'         => $sizeData['code'],  // <--- Acceder a la llave 'code'
            ],
            [
                'value'        => $sizeData['value'], // <--- Acceder a la llave 'value'
                'sort_order'   => $index + 1,
            ]
        );
    }

        $type = Attribute::where('code', 'TYPE')->first();
        if ($type) {
            $type->variantAttributes()->delete();
            $type->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table', function (Blueprint $table) {
            //
        });
    }
};
