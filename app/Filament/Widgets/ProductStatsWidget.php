<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ProductStatsWidget extends BaseWidget
{
    protected ?string $heading = 'Ürün İstatistikleri';

    protected static ?int $sort = 5;
    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $inactiveProducts = Product::where('is_active', false)->count();
        $totalCategories = Category::count();
        $activeCategories = Category::where('is_active', true)->count();

        // Son 7 günün ürün ekleme sayısı (chart için)
        $productsLast7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $productsLast7Days[] = Product::whereDate('created_at', $date)->count();
        }

        $activePercentage = $totalProducts > 0 
            ? round(($activeProducts / $totalProducts) * 100, 1)
            : 0;

        return [
            Stat::make('Aktif Ürünler', Number::format($activeProducts))
                ->description('%' . $activePercentage . ' aktif')
                ->descriptionIcon('heroicon-m-check-circle')
                ->descriptionColor('success')
                ->chart($productsLast7Days)
                ->color('success'),
            
            Stat::make('Pasif Ürünler', Number::format($inactiveProducts))
                ->description('Onay bekliyor')
                ->descriptionIcon('heroicon-m-x-circle')
                ->descriptionColor('danger')
                ->color('danger'),
            
            Stat::make('Toplam Kategori', Number::format($totalCategories))
                ->description($activeCategories . ' aktif kategori')
                ->descriptionIcon('heroicon-m-folder')
                ->color('info'),
            
            Stat::make('Toplam Ürün', Number::format($totalProducts))
                ->description('Sistemdeki tüm ürünler')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
        ];
    }
}

