<?php

namespace App\Filament\Resources\PaymentLinks\Pages;

use App\Filament\Resources\PaymentLinks\PaymentLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentLinks extends ListRecords
{
    protected static string $resource = PaymentLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
        ];
    }
}
