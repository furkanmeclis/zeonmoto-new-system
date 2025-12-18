<?php

namespace App\Filament\Resources\PriceRules\Tables;

use App\Models\Category;
use App\Models\Product;
use App\PriceRuleScope;
use App\PriceRuleType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope')
                    ->label('Kapsam')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        PriceRuleScope::Global => 'Global',
                        PriceRuleScope::Category => 'Kategori',
                        PriceRuleScope::Product => 'Ürün',
                        default => $state?->value ?? '-',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PriceRuleScope::Global => 'info',
                        PriceRuleScope::Category => 'warning',
                        PriceRuleScope::Product => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('scope_id')
                    ->label('Kapsam Detayı')
                    ->formatStateUsing(function ($record) {
                        if (! $record->scope_id) {
                            return '-';
                        }

                        return match ($record->scope) {
                            PriceRuleScope::Category => Category::find($record->scope_id)?->display_name ?? '-',
                            PriceRuleScope::Product => Product::find($record->scope_id)?->name ?? '-',
                            default => '-',
                        };
                    })
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tip')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        PriceRuleType::Percentage => 'Yüzde',
                        PriceRuleType::Amount => 'Sabit Tutar',
                        default => $state?->value ?? '-',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PriceRuleType::Percentage => 'primary',
                        PriceRuleType::Amount => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Değer')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === PriceRuleType::Percentage) {
                            return number_format((float) $state, 2) . '%';
                        }
                        return '₺' . number_format((float) $state, 2);
                    })
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Öncelik')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Başlangıç')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'asc');
    }
}
