<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Matchup;
use App\Observers\MatchupObserver;

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
        //
        Matchup::observe(MatchupObserver::class);
    }
}
