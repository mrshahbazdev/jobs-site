<?php

namespace App\Filament\Resources\JobSourceImages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use App\Jobs\ScrapePakistanJobs;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Collection;

class JobSourceImagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                
                ImageColumn::make('local_image_path')
                    ->label('Thumbnail')
                    ->disk('public'),

                TextColumn::make('title')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('source_page_url')
                    ->label('Original Page')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('local_image_path')
                    ->label('Local Image Link')
                    ->getStateUsing(function ($record) {
                        if (!$record->local_image_path) return 'Not Yet Scraped';
                        $path = storage_path('app/public/' . $record->local_image_path);
                        $version = file_exists($path) ? filemtime($path) : time();
                        return url('storage/' . $record->local_image_path) . '?t=' . $version;
                    })
                    ->copyable()
                    ->limit(30),

                IconColumn::make('local_image_path')
                    ->boolean()
                    ->label('Ad Fetched')
                    ->getStateUsing(fn ($record) => !empty($record->local_image_path)),

                IconColumn::make('is_processed')
                    ->boolean()
                    ->label('Article Done'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('scrape_now')
                    ->label('Scrape (Now)')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->hidden(fn ($record) => !empty($record->local_image_path))
                    ->action(function ($record) {
                        try {
                            $result = \Illuminate\Support\Facades\Artisan::call('scrape:pakistan-jobs', [
                                '--image-id' => $record->id,
                            ]);
                            
                            if ($result === 0) {
                                Notification::make()
                                    ->title('Success')
                                    ->body('Image scraped successfully.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to scrape image. Site might be slow or blocked.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('System Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('scrape_queued')
                    ->label('Scrape (Queued)')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->hidden(fn ($record) => !empty($record->local_image_path))
                    ->action(function ($record) {
                        \App\Jobs\ScrapePakistanJobs::dispatch(false, $record->id);
                        Notification::make()
                            ->title('Image scraping queued...')
                            ->info()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulk_scrape')
                        ->label('Scrape Selected Images')
                        ->icon('heroicon-o-camera')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if (empty($record->local_image_path)) {
                                    ScrapePakistanJobs::dispatch(false, $record->id);
                                }
                            }
                            Notification::make()
                                ->title('Queued ' . $records->count() . ' images for scraping')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Action::make('fetch_links')
                    ->label('Fetch Latest Links')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->modalHeading('Fetching Job Links')
                    ->modalContent(fn () => new HtmlString(Blade::render('@livewire(\'scraper-progress\', [\'mode\' => \'links\'])')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('scrape_all')
                    ->label('Deep Scrape All')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->modalHeading('Full Scraping Progress')
                    ->modalContent(fn () => new HtmlString(Blade::render('@livewire(\'scraper-progress\', [\'mode\' => \'all\'])')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ]);
    }
}
