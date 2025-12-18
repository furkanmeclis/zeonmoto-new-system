<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductExternalsRelationManager extends RelationManager
{
    protected static string $relationship = 'externals';

    protected static ?string $title = 'Harici Eşleştirmeler';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('provider_key')
                    ->label('Sağlayıcı')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                TextInput::make('external_uniqid')
                    ->label('Harici Benzersiz ID')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                TextInput::make('external_hash')
                    ->label('Harici Hash')
                    ->required()
                    ->maxLength(255)
                    ->disabled()
                    ->helperText('Deterministik hash: sha1(provider|uniqid)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('provider_key')
            ->columns([
                TextColumn::make('provider_key')
                    ->label('Sağlayıcı')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('external_uniqid')
                    ->label('Harici Benzersiz ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('external_hash')
                    ->label('Hash')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime()
                    ->sortable()
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
