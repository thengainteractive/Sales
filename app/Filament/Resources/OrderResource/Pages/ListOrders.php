<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Sales\Filament\Resources\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
