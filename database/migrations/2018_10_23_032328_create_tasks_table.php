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
            $table->tinyInteger('type')->default(1);
            $table->unsignedInteger('task_pid');
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('principal_id');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('priority');
            $table->text('desc');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->dateTime('complete_at');
            $table->dateTime('stop_at');
            $table->timestamps();

            $table->foreign('task_pid')
                ->references('id')
                ->on('tasks');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users');

            $table->foreign('principal_id')
                ->references('id')
                ->on('users');

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
