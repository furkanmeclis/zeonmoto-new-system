<?php

namespace App\Filament\Resources\PriceRules\RelationManagers;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryRelationManager extends RelationManager
{
    protected static string $relationship = 'category';

    protected static ?string $title = 'Kategori';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->scope === \App\PriceRuleScope::Category;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Kategori')
                    ->options(Category::query()->where('is_active', true)->pluck('display_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('display_name')
                    ->label('Kategori Adı')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('external_name')
                    ->label('External Adı')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
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
