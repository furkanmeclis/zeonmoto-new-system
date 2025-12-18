<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\OrderStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Gelir Trendi (Son 30 Gün)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Son 30 günün gelir verilerini topla
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = Order::where('status', OrderStatus::Completed)
                ->whereDate('created_at', $date)
                ->sum('total') ?? 0;

            $data[] = $revenue;
            $labels[] = $date->format('d.m');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Gelir (₺)',
                    'data' => $data,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "₺" + value.toLocaleString("tr-TR"); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "₺" + context.parsed.y.toLocaleString("tr-TR"); }',
                    ],
                ],
            ],
        ];
    }
}

