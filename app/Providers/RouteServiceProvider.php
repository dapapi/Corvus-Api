<?php

namespace App\Providers;

use App\Models\Affix;
use App\Models\ApprovalForm\ApprovalForm;
use App\Models\ApprovalForm\Business;
use App\Models\ApprovalForm\Instance;
use App\Models\ApprovalGroup;
use App\Models\Blogger;
use App\Models\BulletinReviewTitle;
use App\Models\Calendar;
use App\Models\ProjectReturnedMoney;
use App\Models\Material;
use App\Models\Project;
use App\Models\Report;
use App\Models\Review;
use App\Models\Announcement;
use App\Models\Issues;
use App\Models\Client;
use App\Models\Production;
use App\Models\ProjectHistorie;
use App\Models\Position;


use App\Models\Draft;
use App\Models\Repository;
use App\Models\ReviewQuestionnaire;
use App\Models\ReviewQuestionItem;
use App\Models\ReviewQuestion;
use App\Models\Contact;
use App\Models\Schedule;
use App\Models\PersonalJob;
use App\Models\PersonalSalary;
use App\Models\PersonalDetail;
use App\Models\GroupRoles;
use App\Models\Department;
use App\Models\Role;
use App\Models\DataDictionarie;
use App\Models\Star;
use App\Models\CommentLog;
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
        Route::bind('projectreturnedmoney', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = ProjectReturnedMoney::findOrFail($id);
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
        Route::bind('commentlog', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = CommentLog::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('production', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Production::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('reviewquestionitem', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = ReviewQuestionItem::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('reviewquestionnaire', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = ReviewQuestionnaire::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('reviewquestion', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = reviewquestion::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('review', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Review::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('repository', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Repository::withTrashed()->findOrFail($id);
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
        Route::bind('report', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Report::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('draft', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Report::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('announcement', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Announcement::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('review', function ($value) {
            try {
                $id = hashid_decode($value);
                //withTrashed()->
                $entity = Review::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('reviewtitle', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = BulletinReviewTitle::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });
        Route::bind('issues', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Issues::withTrashed()->findOrFail($id);
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

        Route::bind('calendar', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Calendar::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('schedule', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Schedule::withTrashed()->findOrFail($id);
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

        Route::bind('material', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Material::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('personalJob', function ($value) {

            try {
                $id = hashid_decode($value);
                $entity = personalJob::withTrashed()->findOrFail($id);

            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('personalSalary', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = personalSalary::withTrashed()->findOrFail($id);


            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('personalDetail', function ($value) {
            try {
                $id = hashid_decode($value);

                $entity = personalDetail::withTrashed()->findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('approval_group', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = ApprovalGroup::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('groupRoles', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = GroupRoles::withTrashed()->findOrFail($id);

            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('role', function ($value) {

            try {
                $id = hashid_decode($value);
                $entity = Role::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('department', function ($value) {
            try {

                $id = hashid_decode($value);
                $entity = Department::findOrFail($id);

            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('dictionaries', function ($value) {

            try {
                $id = hashid_decode($value);
                $entity = DataDictionarie::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('approval', function ($value) {

            try {
                $id = hashid_decode($value);
                $entity = ApprovalForm::where('form_id',$id)->first();
                if (!$entity)
                    throw new Exception('form_id不存在');
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('instance', function ($num) {
            try {
                $entity = Instance::where('form_instance_number',$num)->first();
                if (!$entity)
                    $entity = Business::where('form_instance_number',$num)->first();

                if (!$entity)
                    throw new Exception('number不存在');
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('historie', function ($value) {

            try {
                $id = hashid_decode($value);

                $entity = ProjectHistorie::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('position', function ($value) {

            try {
                $id = hashid_decode($value);
                $entity = Position::findOrFail($id);
            } catch (Exception $exception) {
                abort(404);
            }
            return $entity;
        });

        Route::bind('project_approve', function ($value) {
            try {
                $id = hashid_decode($value);
                $entity = Project::withTrashed()->findOrFail($id);
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
