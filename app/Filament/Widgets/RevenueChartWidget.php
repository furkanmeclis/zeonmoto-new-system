<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\OrderStatus;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Gelir Trendi (Son 30 Gün)';

    public function getDescription(): ?string
    {
        return 'Son 30 gün içindeki günlük gelir miktarları';
    }

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        // Son 30 günlük verileri topla
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d M');
            $revenue = (float) Order::where('status', '!=', OrderStatus::Cancelled)
                ->whereDate('created_at', $date)
                ->sum('total');
            $data[] = round($revenue, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Gelir (₺)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => "function(context) { return '₺' + context.parsed.y.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return '₺' + value.toLocaleString('tr-TR'); }",
                    ],
                ],
            ],
        ];
    }
}

