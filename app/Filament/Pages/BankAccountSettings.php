<?php

namespace App\Filament\Pages;

use App\Settings\BankAccountSettings as BankAccountSettingsClass;
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

class BankAccountSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Havale/EFT Bilgileri';

    protected static ?string $title = 'Havale/EFT Bilgileri';

    protected static string | UnitEnum | null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.bank-account-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(BankAccountSettingsClass::class);

        $this->data = [
            'name' => $settings->name ?? '',
            'bank' => $settings->bank ?? '',
            'iban' => $settings->iban ?? '',
            'branch' => $settings->branch ?? '',
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Banka Hesap Bilgileri')
                    ->description('Havale ve EFT ödemeleri için kullanılacak banka hesap bilgilerini girin.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Hesap Sahibi Adı')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Banka hesabının sahibinin tam adı.')
                            ->placeholder('Örn: Zeon Moto Ticaret Ltd. Şti.'),
                        
                        TextInput::make('bank')
                            ->label('Banka Adı')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Banka adı.')
                            ->placeholder('Örn: Ziraat Bankası'),
                        
                        TextInput::make('iban')
                            ->label('IBAN')
                            ->required()
                            ->maxLength(34)
                            ->helperText('IBAN numarası (34 karakter).')
                            ->placeholder('TR00 0000 0000 0000 0000 0000 00')
                            ->formatStateUsing(fn ($state) => $this->formatIban($state))
                            ->dehydrateStateUsing(fn ($state) => $this->normalizeIban($state)),
                        
                        TextInput::make('branch')
                            ->label('Şube')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Banka şube adı veya kodu.')
                            ->placeholder('Örn: Merkez Şube'),
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
            $settings = app(BankAccountSettingsClass::class);
            
            // Ayarları set et
            $settings->name = $data['name'] ?? '';
            $settings->bank = $data['bank'] ?? '';
            $settings->iban = $this->normalizeIban($data['iban'] ?? '');
            $settings->branch = $data['branch'] ?? '';
            
            // Settings'i kaydet
            $settings->save();

            Notification::make()
                ->title('Ayarlar Kaydedildi')
                ->body('Havale/EFT bilgileri başarıyla kaydedildi.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hata')
                ->body('Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->danger()
                ->send();
            
            \Log::error('Bank Account Settings save error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Format IBAN for display (add spaces every 4 characters)
     */
    protected function formatIban(?string $iban): string
    {
        if (empty($iban)) {
            return '';
        }

        $normalized = $this->normalizeIban($iban);
        return chunk_split($normalized, 4, ' ');
    }

    /**
     * Normalize IBAN (remove spaces and convert to uppercase)
     */
    protected function normalizeIban(?string $iban): string
    {
        if (empty($iban)) {
            return '';
        }

        return strtoupper(preg_replace('/\s+/', '', $iban));
    }

    public static function getMaxWidth(): MaxWidth
    {
        return MaxWidth::FourXL;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'bank-account-settings';
    }
}
