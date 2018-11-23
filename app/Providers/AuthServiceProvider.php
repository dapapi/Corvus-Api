<?php

namespace App\Providers;

use App\Models\Blogger;
use App\Models\Client;
use App\Models\Task;
use App\Policies\BloggerPolicy;
use App\Policies\ClientPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        Client::class => ClientPolicy::class,
        Task::class => TaskPolicy::class,
        Blogger::class => BloggerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
    }
}
