<?php

namespace App\Filament\Resources\Subscribers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email_or_whatsapp')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => true])),

                    \Filament\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => false])),

                    \Filament\Actions\BulkAction::make('export_csv')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $filename = 'subscribers-' . now()->format('Y-m-d') . '.csv';
                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => "attachment; filename=\"$filename\"",
                            ];

                            $callback = function () use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['ID', 'Name', 'Email/WhatsApp', 'Status', 'Date Joined']);

                                foreach ($records as $record) {
                                    fputcsv($file, [
                                        $record->id,
                                        $record->name,
                                        $record->email_or_whatsapp,
                                        $record->is_active ? 'Active' : 'Inactive',
                                        $record->created_at->toDateTimeString(),
                                    ]);
                                }

                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
