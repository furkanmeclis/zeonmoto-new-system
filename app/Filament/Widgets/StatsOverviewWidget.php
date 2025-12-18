<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\OrderStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Son 7 günlük sipariş trendi
        $ordersTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $ordersTrend[] = Order::whereDate('created_at', $date)->count();
        }

        // Son 7 günlük gelir trendi
        $revenueTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenueTrend[] = (float) Order::where('status', '!=', OrderStatus::Cancelled)
                ->whereDate('created_at', $date)
                ->sum('total');
        }

        // Son 7 günlük müşteri trendi
        $customersTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $customersTrend[] = Customer::whereDate('created_at', $date)->count();
        }

        // Bu ay toplam gelir
        $monthlyRevenue = Order::where('status', '!=', OrderStatus::Cancelled)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        // Geçen ay toplam gelir
        $lastMonthRevenue = Order::where('status', '!=', OrderStatus::Cancelled)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total');

        // Gelir değişim yüzdesi
        $revenueChange = $lastMonthRevenue > 0 
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        return [
            Stat::make('Toplam Ürün', Product::count())
                ->description('Aktif: ' . Product::where('is_active', true)->count())
                ->descriptionIcon('heroicon-o-cube')
                ->color('success')
                ->chart($ordersTrend),

            Stat::make('Toplam Sipariş', Order::count())
                ->description('Yeni: ' . Order::where('status', OrderStatus::New)->count())
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('info')
                ->chart($ordersTrend),

            Stat::make('Toplam Müşteri', Customer::count())
                ->description('Son 30 gün: ' . Customer::where('created_at', '>=', now()->subDays(30))->count())
                ->descriptionIcon('heroicon-o-users')
                ->color('warning')
                ->chart($customersTrend),

            Stat::make('Toplam Ciro', '₺' . number_format(Order::where('status', '!=', OrderStatus::Cancelled)->sum('total'), 2, ',', '.'))
                ->description(
                    'Bu ay: ₺' . number_format($monthlyRevenue, 2, ',', '.') . 
                    ($revenueChange != 0 ? ' (' . ($revenueChange > 0 ? '+' : '') . $revenueChange . '%)' : '')
                )
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('success')
                ->chart($revenueTrend),
        ];
    }
}
