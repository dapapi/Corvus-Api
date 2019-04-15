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
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
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

        //对列失败
        Queue::failing(function (JobFailed $event){
            Log::info("失败任务，连接:".$event->connectionName);
            Log::info("失败任务,job:");
            Log::info($event->job->getName());
            Log::info($event->exception);
        });
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
