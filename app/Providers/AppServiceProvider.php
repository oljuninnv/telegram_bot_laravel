<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ReportService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ReportService::class, function ($app) {
            return new ReportService();
        });
    }
}