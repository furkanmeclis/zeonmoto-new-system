<?php

namespace App\Filament\Pages;

use App\Settings\PaymentCommissionSettings as PaymentCommissionSettingsClass;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use UnitEnum;

class PaymentCommissionSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-percent-badge';

    protected static ?string $navigationLabel = 'Ödeme Komisyon Ayarları';

    protected static ?string $title = 'Ödeme Komisyon Ayarları';

    protected static string | UnitEnum | null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.payment-commission-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(PaymentCommissionSettingsClass::class);

        $this->data = [
            'commission_rate' => $settings->commission_rate ?? 3.5,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('PayTR Link Komisyon Ayarları')
                    ->description('PayTR link ile ödeme seçildiğinde uygulanacak komisyon oranını buradan yönetebilirsiniz. Komisyon oranı yüzde olarak belirlenir ve toplam fiyata eklenir.')
                    ->schema([
                        TextInput::make('commission_rate')
                            ->label('Komisyon Oranı (%)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('PayTR link ile ödeme seçildiğinde uygulanacak komisyon oranı. Örnek: 3.5 için %3.5 komisyon uygulanır.')
                            ->default(3.5),
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
            $settings = app(PaymentCommissionSettingsClass::class);
            
            // Komisyon oranını set et
            $settings->commission_rate = isset($data['commission_rate']) ? (float) $data['commission_rate'] : 3.5;
            
            // Settings'i kaydet
            $settings->save();

            Notification::make()
                ->title('Ayarlar Kaydedildi')
                ->body('Ödeme komisyon ayarları başarıyla kaydedildi.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
            
            \Log::error('Payment Commission Settings save error', [
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

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'payment-commission-settings';
    }
}
