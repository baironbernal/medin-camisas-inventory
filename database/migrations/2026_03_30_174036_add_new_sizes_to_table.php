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
        // Corrección 1: Usar where() para buscar por columna 'code'
    $sizeAttribute = Attribute::where('code', 'SIZE')->first();

    // Si no existe, lo creamos para evitar el error de Integrity Constraint
    if (!$sizeAttribute) {
      return;
    }

    $sizes = ['Única', '10', '12', '14', '16', '18', '20'];

    foreach ($sizes as $index => $sizeValue) {
        // Corrección 2: Usar updateOrCreate para que puedas re-ejecutar 
        // la migración sin que explote por valores duplicados.
        AttributeValue::updateOrCreate(
            [
                'attribute_id' => $sizeAttribute->id,
                'value' => $sizeValue,
            ],
            [
                'sort_order' => $index + 1,
            ]
        );
    }

    // Corrección 3: Buscar el atributo 'TYPE' de forma segura antes de borrar
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
