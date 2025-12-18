<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\OrderStatus;
use Filament\Widgets\ChartWidget;

class OrderStatusWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Sipariş Durumları';

    public function getDescription(): ?string
    {
        return 'Sipariş durumlarına göre dağılım';
    }

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $statusLabels = [];
        $statusData = [];
        $statusColors = [];

        // Her durum için sayıları topla
        foreach (OrderStatus::cases() as $status) {
            $count = Order::where('status', $status)->count();
            
            if ($count > 0) {
                $statusLabels[] = $this->getStatusLabel($status);
                $statusData[] = $count;
                $statusColors[] = $this->getStatusColor($status);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sipariş Sayısı',
                    'data' => $statusData,
                    'backgroundColor' => $statusColors,
                    'borderColor' => array_map(fn($color) => str_replace('0.5', '1', $color), $statusColors),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $statusLabels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }

    /**
     * Sipariş durumu için Türkçe etiket döndürür.
     */
    protected function getStatusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Draft => 'Taslak',
            OrderStatus::New => 'Yeni',
            OrderStatus::Preparing => 'Hazırlanıyor',
            OrderStatus::Completed => 'Tamamlandı',
            OrderStatus::Cancelled => 'İptal Edildi',
        };
    }

    /**
     * Sipariş durumu için renk döndürür.
     */
    protected function getStatusColor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Draft => 'rgba(107, 114, 128, 0.5)', // gray
            OrderStatus::New => 'rgba(251, 191, 36, 0.5)', // yellow/warning
            OrderStatus::Preparing => 'rgba(59, 130, 246, 0.5)', // blue/info
            OrderStatus::Completed => 'rgba(34, 197, 94, 0.5)', // green/success
            OrderStatus::Cancelled => 'rgba(239, 68, 68, 0.5)', // red/danger
        };
    }
}

