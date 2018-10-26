<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\ModuleableType;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191);

        Relation::morphMap([
            ModuleableType::TASK => Task::class,
            ModuleableType::PROJECT => Project::class,
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
