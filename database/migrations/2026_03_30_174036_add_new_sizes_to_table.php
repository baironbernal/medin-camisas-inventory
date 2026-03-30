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
        ['code' => 'DIEZ', 'value' => '10'], 
        ['code' => 'DOCE', 'value' => '12'], 
        ['code' => 'CATORCE', 'value' => '14'], 
        ['code' => 'DIECISEIS', 'value' => '16'], 
        ['code' => 'DIECIOCHO', 'value' => '18'], 
        ['code' => 'VEINTE', 'value' => '20']
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
            $type->attributeValues()->delete();
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
