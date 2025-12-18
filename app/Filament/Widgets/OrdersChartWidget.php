<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\OrderStatus;
use Filament\Widgets\ChartWidget;

class OrdersChartWidget extends ChartWidget
{
    protected ?string $heading = 'Sipariş Durumları';

    protected static ?int $sort = 2;
    public function getColumns(): int | array
{
    return 2;
}

    protected function getData(): array
    {
        $draftCount = Order::where('status', OrderStatus::Draft)->count();
        $newCount = Order::where('status', OrderStatus::New)->count();
        $preparingCount = Order::where('status', OrderStatus::Preparing)->count();
        $completedCount = Order::where('status', OrderStatus::Completed)->count();
        $cancelledCount = Order::where('status', OrderStatus::Cancelled)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Sipariş Durumları',
                    'data' => [
                        $draftCount,
                        $newCount,
                        $preparingCount,
                        $completedCount,
                        $cancelledCount,
                    ],
                    'backgroundColor' => [
                        'rgb(156, 163, 175)', // gray - Draft
                        'rgb(251, 191, 36)',  // amber - New
                        'rgb(59, 130, 246)',  // blue - Preparing
                        'rgb(34, 197, 94)',   // green - Completed
                        'rgb(239, 68, 68)',   // red - Cancelled
                    ],
                ],
            ],
            'labels' => [
                'Taslak',
                'Yeni',
                'Hazırlanıyor',
                'Tamamlandı',
                'İptal Edildi',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}

