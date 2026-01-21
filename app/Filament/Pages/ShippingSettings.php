<?php

namespace App\Filament\Pages;

use App\Settings\ShippingSettings as ShippingSettingsClass;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use UnitEnum;

class ShippingSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Kargo Ayarları';

    protected static ?string $title = 'Kargo Ayarları';

    protected static string | UnitEnum | null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.shipping-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(ShippingSettingsClass::class);

        $this->data = [
            'free_shipping_limit_with_pin' => $settings->free_shipping_limit_with_pin ?? 50000.00,
            'free_shipping_limit_without_pin' => $settings->free_shipping_limit_without_pin ?? 50000.00,
            'shipping_cost' => $settings->shipping_cost ?? 150.00,
            'charge_pin_verified_customers' => $settings->charge_pin_verified_customers ?? false,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Kargo Ücretsiz Alt Limitleri')
                    ->description('PIN durumuna göre ücretsiz kargo için minimum sepet tutarlarını belirleyin.')
                    ->schema([
                        TextInput::make('free_shipping_limit_with_pin')
                            ->label('Kargo Ücretsiz İçin Alt Limit TL (PIN Girilmiş)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('TL')
                            ->helperText('PIN girişi yapmış müşteriler için ücretsiz kargo alt limiti.')
                            ->default(50000.00),
                        
                        TextInput::make('free_shipping_limit_without_pin')
                            ->label('Kargo Ücretsiz İçin Alt Limit TL (PIN Girilmemiş)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('TL')
                            ->helperText('PIN girişi yapmamış müşteriler için ücretsiz kargo alt limiti.')
                            ->default(50000.00),
                    ])
                    ->columns(2),
                
                Section::make('Kargo Ücreti Ayarları')
                    ->description('Kargo ücreti ve PIN doğrulamalı müşteriler için kargo ücreti politikasını belirleyin.')
                    ->schema([
                        TextInput::make('shipping_cost')
                            ->label('Kargo Ücreti (TL)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('TL')
                            ->helperText('Sepet tutarı alt limitin altındayken uygulanacak kargo ücreti.')
                            ->default(150.00),
                        
                        Toggle::make('charge_pin_verified_customers')
                            ->label('PIN Girişi Yapmış Müşterilerden Kargo Ücreti Alınacak mı?')
                            ->helperText('Bu seçenek kapalıysa, PIN girişi yapmış müşterilerden alt limitin altında bile olsa kargo ücreti alınmaz.')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Kaydet')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('saveSettings'),
        ];
    }

    public function saveSettings(): void
    {
        $data = $this->data;

        try {
            // Settings'i resolve et
            $settings = app(ShippingSettingsClass::class);
            
            // Ayarları set et
            $settings->free_shipping_limit_with_pin = isset($data['free_shipping_limit_with_pin']) 
                ? (float) $data['free_shipping_limit_with_pin'] 
                : 50000.00;
            
            $settings->free_shipping_limit_without_pin = isset($data['free_shipping_limit_without_pin']) 
                ? (float) $data['free_shipping_limit_without_pin'] 
                : 50000.00;
            
            $settings->shipping_cost = isset($data['shipping_cost']) 
                ? (float) $data['shipping_cost'] 
                : 150.00;
            
            $settings->charge_pin_verified_customers = isset($data['charge_pin_verified_customers']) 
                ? (bool) $data['charge_pin_verified_customers'] 
                : false;
            
            // Settings'i kaydet
            $settings->save();

            Notification::make()
                ->title('Ayarlar Kaydedildi')
                ->body('Kargo ayarları başarıyla kaydedildi.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
            
            \Log::error('Shipping Settings save error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
        }
    }

    public static function getMaxWidth(): MaxWidth
    {
        return MaxWidth::FourXL;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'shipping-settings';
    }
}
