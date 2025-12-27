<?php

namespace App\Filament\Pages;

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
use FurkanMeclis\PayTRLink\Settings\PayTRSettings as PayTRLinkSettings;
use UnitEnum;

class PayTRSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'PayTR Ayarları';

    protected static ?string $title = 'PayTR Ayarları';

    protected static string | UnitEnum | null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.paytr-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(PayTRLinkSettings::class);

        $this->data = [
            'merchant_id' => $settings->merchant_id ?? config('paytr-link.merchant_id'),
            'merchant_key' => $settings->merchant_key ?? config('paytr-link.merchant_key'),
            'merchant_salt' => $settings->merchant_salt ?? config('paytr-link.merchant_salt'),
            'debug_on' => $settings->debug_on ?? (bool) config('paytr-link.debug_on', 1),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('PayTR API Ayarları')
                    ->description('PayTR Link API entegrasyonu için gerekli bilgileri buradan yönetebilirsiniz. Bu ayarlar veritabanında saklanır ve config dosyasındaki değerlerin üzerine yazılır.')
                    ->schema([
                        TextInput::make('merchant_id')
                            ->label('Merchant ID')
                            ->required()
                            ->maxLength(255)
                            ->helperText('PayTR panelinden aldığınız Merchant ID değeri'),

                        TextInput::make('merchant_key')
                            ->label('Merchant Key')
                            ->required()
                            ->maxLength(255)
                            ->password()
                            ->revealable()
                            ->helperText('PayTR panelinden aldığınız Merchant Key değeri'),

                        TextInput::make('merchant_salt')
                            ->label('Merchant Salt')
                            ->required()
                            ->maxLength(255)
                            ->password()
                            ->revealable()
                            ->helperText('PayTR panelinden aldığınız Merchant Salt değeri'),

                        Toggle::make('debug_on')
                            ->label('Debug Modu')
                            ->helperText('Debug modu aktif olduğunda API istekleri ve yanıtları loglanır.')
                            ->default(true),
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
            $settings = app(PayTRLinkSettings::class);
            
            // Tüm property'leri açıkça set et - Spatie Settings tüm property'lerin set edilmesini bekler
            // Null değerler yerine boş string veya varsayılan değerler kullan
            $settings->merchant_id = filled($data['merchant_id'] ?? null) ? (string) $data['merchant_id'] : '';
            $settings->merchant_key = filled($data['merchant_key'] ?? null) ? (string) $data['merchant_key'] : '';
            $settings->merchant_salt = filled($data['merchant_salt'] ?? null) ? (string) $data['merchant_salt'] : '';
            $settings->debug_on = isset($data['debug_on']) ? (bool) $data['debug_on'] : false;
            
            // Settings'i kaydet - tüm property'ler set edildi
            $settings->save();

            Notification::make()
                ->title('Ayarlar Kaydedildi')
                ->body('PayTR ayarları başarıyla kaydedildi.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Ayarlar: ' .json_encode([
                    "merchant_id" => $data['merchant_id'],
                    "merchant_key" => $data['merchant_key'],
                    "merchant_salt" => $data['merchant_salt'],
                    "debug_on" => $data['debug_on'],
                ]))
                ->danger()
                ->send();
            Notification::make()
                ->title('Hata')
                ->body('Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
            
            \Log::error('PayTR Settings save error', [
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
        return 'paytr-settings';
    }
}
