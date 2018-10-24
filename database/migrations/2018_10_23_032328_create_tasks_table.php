<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('type_id')->nullable();
            $table->unsignedInteger('task_pid')->nullable();
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('principal_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('priority')->nullable();
            $table->text('desc')->nullable();
            $table->boolean('privacy');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->dateTime('complete_at')->nullable();
            $table->dateTime('stop_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('creator_id')
                ->references('id')
                ->on('users');

            $table->foreign('principal_id')
                ->references('id')
                ->on('users');

            $table->foreign('type_id')
                ->references('id')
                ->on('task_types');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
