<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL older versions
        Schema::defaultStringLength(191);
        
        // Register the schedule
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Run voucher sync every 5 minutes to mark used vouchers
            $schedule->command('vouchers:sync-usage')
                ->everyFiveMinutes()
                ->appendOutputTo(storage_path('logs/voucher-sync.log'));
        });
    }
}
