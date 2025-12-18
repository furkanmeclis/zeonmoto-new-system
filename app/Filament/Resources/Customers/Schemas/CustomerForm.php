<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kişisel Bilgiler')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Ad')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('Soyad')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Adres Bilgileri')
                    ->schema([
                        TextInput::make('city')
                            ->label('Şehir')
                            ->maxLength(255),
                        TextInput::make('district')
                            ->label('İlçe')
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Adres')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Notlar')
                    ->schema([
                        Textarea::make('note')
                            ->label('Not')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
