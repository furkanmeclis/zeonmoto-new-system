<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\PriceRuleScope;
use App\PriceRuleType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'priceRules';

    protected static ?string $title = 'Fiyat Kuralları';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tip')
                    ->options([
                        PriceRuleType::Percentage->value => 'Yüzde (%)',
                        PriceRuleType::Amount->value => 'Sabit Tutar (₺)',
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('value')
                    ->label('Değer')
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->prefix(fn (callable $get) => $get('type') === PriceRuleType::Percentage->value ? '%' : '₺')
                    ->helperText(fn (callable $get) => match ($get('type')) {
                        PriceRuleType::Percentage->value => 'Örnek: 10 = %10 artış, -5 = %5 indirim',
                        PriceRuleType::Amount->value => 'Örnek: 50 = 50₺ artış, -20 = 20₺ indirim',
                        default => '',
                    }),
                TextInput::make('priority')
                    ->label('Öncelik')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('Düşük sayı = önce uygulanır'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PriceRuleType::Percentage => 'primary',
                        PriceRuleType::Amount => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        PriceRuleType::Percentage => 'Yüzde',
                        PriceRuleType::Amount => 'Sabit Tutar',
                        default => '-',
                    }),
                TextColumn::make('value')
                    ->label('Değer')
                    ->formatStateUsing(function ($record) {
                        if ($record->type === PriceRuleType::Percentage) {
                            return number_format($record->value, 2) . '%';
                        }
                        return '₺' . number_format($record->value, 2);
                    })
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Öncelik')
                    ->sortable(),
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
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}

