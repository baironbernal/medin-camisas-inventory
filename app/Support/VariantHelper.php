<?php

namespace App\Support;

use Filament\Forms\Get;

class VariantHelper
{
    /**
     * Determines whether the Inventory section should be visible
     * in the "Generar Variantes" action form.
     *
     * All three attribute groups (size, color, material) must be
     * fully filled before inventory configuration makes sense.
     */
    public static function shouldShowInventory(Get $get): bool
    {
        $fullCurve          = $get('full_curve');
        $sizes              = $get('sizes') ?? [];
        $color              = $get('colors');
        $material           = $get('materials');
        $otherColor         = $get('other_color');
        $otherColorValue    = $get('other_color_value');
        $otherColorHex      = $get('other_color_hex');
        $otherMaterial      = $get('other_material');
        $otherMaterialValue = $get('other_material_value');

        $sizeSelected     = $fullCurve || ! empty($sizes);
        $colorSelected    = ($color && ! $otherColor) || ($otherColor && $otherColorValue && $otherColorHex);
        $materialSelected = ($material && ! $otherMaterial) || ($otherMaterial && $otherMaterialValue);

        return $sizeSelected && $colorSelected && $materialSelected;
    }
}
