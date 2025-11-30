<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Select::make('teacher_id')
                            ->label('Teacher')
                            ->relationship('teacher', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}
