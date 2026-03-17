<?php

namespace App\Filament\Resources\DiscountRuleResource\Pages;

use App\Filament\Resources\DiscountRuleResource;
use App\Models\DiscountRule;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDiscountRule extends CreateRecord
{
    protected static string $resource = DiscountRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateDiscountRuleData($data);

        return $data;
    }

    private function validateDiscountRuleData(array $data, ?int $ignoreId = null): void
    {
        // 🔒 RULE 1: max must be >= min
        if (!is_null($data['max_quantity']) && $data['min_quantity'] > $data['max_quantity']) {
            throw ValidationException::withMessages([
                'data.max_quantity' => 'Max must be greater than or equal to Min.',
            ]);
        }

        // 🔒 RULE 2: Prevent overlapping ranges
        $query = DiscountRule::query()
            ->where('is_active', true)
            ->where(function ($q) use ($data) {
                $min = $data['min_quantity'];
                $max = $data['max_quantity'];

                $q->where(function ($q2) use ($min, $max) {
                    $q2->where('min_quantity', '<=', $max ?? $min)
                       ->where(function ($q3) use ($min) {
                           $q3->where('max_quantity', '>=', $min)
                              ->orWhereNull('max_quantity');
                       });
                });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'data.min_quantity' => 'This range overlaps with an existing rule.',
            ]);
        }
    }
}
