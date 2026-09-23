<?php

namespace App\Filament\Widgets;

use App\Models\JobListing;
use App\Models\Subscriber;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Jobs', JobListing::where('is_active', true)->count())
                ->description('Currently visible on site')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success'),
            Stat::make('Added Today', JobListing::whereDate('created_at', now()->toDateString())->count())
                ->description('New listings added today')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
            Stat::make('Expiring Soon', JobListing::where('is_active', true)->where('deadline', '<=', now()->addDays(2)->toDateString())->count())
                ->description('Ending in 48 hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Total Subscribers', Subscriber::count())
                ->description('Email & WhatsApp alerts')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
