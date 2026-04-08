<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Steps\PaymentStep;
use App\Filament\Resources\OrderResource\Steps\ProductsStep;
use App\Filament\Resources\OrderResource\Steps\WholesalerStep;
use App\Models\Order;
use App\Services\OrderCreationService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateOrder extends CreateRecord
{
    use HasWizard;

    protected static string $resource = OrderResource::class;

    protected function getSteps(): array
    {
        return [
            WholesalerStep::make(),
            ProductsStep::make(),
            PaymentStep::make(),
        ];
    }

    protected function handleRecordCreation(array $data): Order
    {
        return app(OrderCreationService::class)->createFromWizardData($data);
    }
}
