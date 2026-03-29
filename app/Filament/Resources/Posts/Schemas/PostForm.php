<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->lazy()
                    ->afterStateUpdated(fn ($set, $state) => $set('slug', \Illuminate\Support\Str::slug($state))),
                
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                FileUpload::make('image')
                    ->image()
                    ->directory('blog')
                    ->disk('public'),

                \Filament\Forms\Components\RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),

                Toggle::make('is_published')
                    ->label('Published')
                    ->default(false),
            ]);
    }
}
