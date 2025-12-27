<?php

namespace App\Filament\Resources\PaymentLinks;

use App\Filament\Resources\PaymentLinks\Pages\CreatePaymentLink;
use App\Filament\Resources\PaymentLinks\Pages\EditPaymentLink;
use App\Filament\Resources\PaymentLinks\Pages\ListPaymentLinks;
use App\Filament\Resources\PaymentLinks\Pages\ViewPaymentLink;
use App\Filament\Resources\PaymentLinks\Schemas\PaymentLinkForm;
use App\Filament\Resources\PaymentLinks\Schemas\PaymentLinkInfolist;
use App\Filament\Resources\PaymentLinks\Tables\PaymentLinksTable;
use App\Models\PaymentLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PaymentLinkResource extends Resource
{
    protected static ?string $model = PaymentLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $navigationLabel = 'Ödeme Linkleri';

    protected static string|UnitEnum|null $navigationGroup = 'Ödeme Linkleri';

    protected static ?string $modelLabel = 'Ödeme Linki';

    protected static ?string $pluralModelLabel = 'Ödeme Linkleri';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PaymentLinkForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentLinkInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentLinksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentLinks::route('/'),
            'view' => ViewPaymentLink::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
