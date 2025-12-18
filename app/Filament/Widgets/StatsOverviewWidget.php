<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', \App\OrderStatus::Completed)->sum('total');
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();

        // Son 7 günün sipariş sayısı (chart için)
        $ordersLast7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $ordersLast7Days[] = Order::whereDate('created_at', $date)->count();
        }

        // Son 7 günün geliri (chart için)
        $revenueLast7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenueLast7Days[] = Order::where('status', \App\OrderStatus::Completed)
                ->whereDate('created_at', $date)
                ->sum('total') ?? 0;
        }

        // Bugünkü sipariş sayısı
        $todayOrders = Order::whereDate('created_at', today())->count();
        $yesterdayOrders = Order::whereDate('created_at', today()->subDay())->count();
        $ordersChange = $yesterdayOrders > 0 
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
            : ($todayOrders > 0 ? 100 : 0);

        // Bugünkü gelir
        $todayRevenue = Order::where('status', \App\OrderStatus::Completed)
            ->whereDate('created_at', today())
            ->sum('total') ?? 0;
        $yesterdayRevenue = Order::where('status', \App\OrderStatus::Completed)
            ->whereDate('created_at', today()->subDay())
            ->sum('total') ?? 0;
        $revenueChange = $yesterdayRevenue > 0 
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : ($todayRevenue > 0 ? 100 : 0);

        return [
            Stat::make('Toplam Sipariş', Number::format($totalOrders))
                ->description($ordersChange >= 0 ? '+' . $ordersChange . '% artış' : $ordersChange . '% azalış')
                ->descriptionIcon($ordersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($ordersChange >= 0 ? 'success' : 'danger')
                ->chart($ordersLast7Days)
                ->color('primary'),
            
            Stat::make('Toplam Gelir', Number::currency($totalRevenue, 'TRY'))
                ->description($revenueChange >= 0 ? '+' . $revenueChange . '% artış' : $revenueChange . '% azalış')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($revenueLast7Days)
                ->color('success'),
            
            Stat::make('Toplam Müşteri', Number::format($totalCustomers))
                ->description('Kayıtlı müşteri sayısı')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
            
            Stat::make('Toplam Ürün', Number::format($totalProducts))
                ->description($activeProducts . ' aktif ürün')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),
        ];
    }
}

