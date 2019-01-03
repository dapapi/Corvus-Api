<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBulletinReviewTitleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bulletin_review_title', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('creator_id');      //创建人
            $table->unsignedInteger('reviewer_id');     //评审人
            $table->unsignedInteger('comment_id');    //评论
            $table->string('template_name', 100);
            $table->string('answer', 100);
            $table->string('title', 100);
            $table->unsignedInteger('status');
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
        Schema::dropIfExists('bulletin_review_title');
    }
}
