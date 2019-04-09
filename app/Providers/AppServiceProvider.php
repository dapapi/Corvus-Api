<?php

namespace App\Providers;

use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Instance;
use App\Models\Blogger;
use App\Models\Calendar;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Announcement;
use App\Models\Star;
use App\Models\Task;
use App\Models\Issues;
use App\Models\Trail;
use App\Models\Report;
use App\Models\Department;
use App\Models\Repository;
use App\User;

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
            ModuleableType::STAR => Star::class,
            ModuleableType::CLIENT => Client::class,
            ModuleableType::CONTACT => Contact::class,
            ModuleableType::TRAIL => Trail::class,
            ModuleableType::BLOGGER => Blogger::class,
            ModuleableType::CALENDAR => Calendar::class,
            ModuleableType::SCHEDULE => Schedule::class,
            ModuleableType::USER => User::class,
            ModuleableType::SCHEDULE => Schedule::class,
            ModuleableType::ISSUES => Issues::class,
            ModuleableType::REPORT => Report::class,
            ModuleableType::ANNOUNCEMENT => Announcement::class,
            ModuleableType::DEPARTMENT => Department::class,
            ModuleableType::REPOSITORY => Repository::class,
            ModuleableType::CONTRACT => Contract::class,
            ModuleableType::BUSINESS => Business::class,
            ModuleableType::INSTANCE => Instance::class,

            //TODO
        ]);

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
//        $this->app->make('auth')->provider('redis', function ($app, $config) {
//            return new RedisUserProvider($app['hash'], $config['model']);
//        });
    }
}
