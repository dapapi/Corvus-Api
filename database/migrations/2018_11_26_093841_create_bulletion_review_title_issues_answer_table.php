<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBulletionReviewTitleIssuesAnswerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulletion_review_title_issues_answer', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('bulletion_review_title_id');
            $table->string('issues',30)->comment('问题内容');
            $table->string('answer',30)->comment('答案内容');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bulletion_review_title_issues_answer');
    }
}
