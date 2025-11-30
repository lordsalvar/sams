<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        $this->getFormContentComponent()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
