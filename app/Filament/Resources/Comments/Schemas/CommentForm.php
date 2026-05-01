<?php

namespace App\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->required()
                    ->rows(4),
                Toggle::make('is_approved')
                    ->label('Approved'),
            ]);
    }
}
