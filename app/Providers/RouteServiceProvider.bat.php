<?php

namespace App\Providers;

use App\Models\Affix;
use App\Models\Blogger;
use App\Models\Project;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Star;
use App\Models\Task;
use App\Models\Trail;
use App\User;
use Exception;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();

        Route::bind('task', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Task::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('project', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Project::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('client', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Client::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('contact', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Contact::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('trail', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Trail::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('affix', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Affix::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('star', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Star::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('client', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Client::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('contact', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Contact::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('trail', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Trail::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('blogger', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Blogger::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('user', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = User::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }
}