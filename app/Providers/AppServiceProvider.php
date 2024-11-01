<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
       // Paginator::useBootstrapFive();
       Paginator::defaultView("vendor.pagination.yazilimegitimPagination");

        Carbon::setLocale(config("app.locale"));

        View::composer(['front.*', 'mail::header', 'email.*'], function($view){
//            dd($view);
            $settings = Settings::first();
            $categories = Category::query()->where("status", 1)->get();
            $view->with("settings", $settings)->with("categories", $categories);
        });


    }
}
