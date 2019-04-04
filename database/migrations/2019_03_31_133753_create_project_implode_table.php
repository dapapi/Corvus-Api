<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectImplodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_implode', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary('id')->comment('项目id');
            $table->string('project_name', 40)->comment('项目名称');
            $table->unsignedTinyInteger('project_type')->comment('项目类型');
            $table->unsignedTinyInteger('project_priority')->comment('项目优先级');
            $table->string('project_start_at', 19)->comment('项目开始时间');
            $table->string('project_end_at', 19)->comment('项目结束时间');
            $table->string('project_store_at', 19)->comment('项目录入时间');
            $table->unsignedInteger('principal_id')->comment('项目负责人id');
            $table->string('principal', 10)->comment('项目负责人');
            $table->float('projected_expenditure', 12, 2)->comment('预计支出');
            $table->float('expenditure', 12, 2)->comment('实际支出');
            $table->float('trail_fee', 12, 2)->comment('预计订单收入');
            $table->float('revenue', 12, 2)->comment('实际收入');
            $table->unsignedInteger('creator_id')->comment('录入人id');
            $table->string('creator', 10)->comment('录入人');
            $table->unsignedInteger('department_id')->comment('负责人所在部门id');
            $table->unsignedInteger('department')->comment('负责人所在部门');
            $table->unsignedTinyInteger('project_status')->comment('项目状态');
            # 项目额外字段
            $table->string('sign_at', 19)->comment('签单时间,字段id22');
            $table->string('launch_at', 19)->comment('上线时间,字段id52');
            $table->string('platforms', 40)->comment('平台,字段id11');
            $table->string('show_type', 10)->comment('节目类型,字段id31');
            $table->string('guest_type', 8)->comment('嘉宾类型,字段id32');
            $table->string('record_at', 19)->comment('录制时间,字段id34');
            $table->string('movie_type', 8)->comment('影视类型,字段id7');
            $table->string('theme', 50)->comment('题材,字段id9');
            $table->string('team_info', 50)->comment('选角团队,字段id23'); # 随便填写
            $table->text('follow_up')->comment('跟进情况,字段id24'); # 长文本
            $table->string('walk_through_at', 19)->comment('试戏时间,字段id25');
            $table->string('walk_through_location', 80)->comment('试戏地点,字段id26');
            $table->text('walk_through_feedback')->comment('试戏反馈,字段id27'); # 长文本
            $table->text('follow_up_result')->comment('跟进结果,字段id28'); # 长文本
            $table->string('agreement_fee', 40)->comment('合约费用,字段id55');
            $table->string('multi_channel', 40)->comment('投放平台,字段id54');
            # 线索部分
            $table->unsignedTinyInteger('resource_type')->comment('线索来源');
            $table->unsignedTinyInteger('cooperation_type')->comment('合作类型');
            $table->unsignedTinyInteger('trail_status')->comment('线索状态');
            # 目标艺人 一对多问题单拎表 本表存字符串$table->unsignedInteger('project_id')->comment('项目id');
            $table->string('team_m', 50)->comment('目标艺人所在M组'); # 一对多问题
            $table->string('team_producer', 50)->comment('目标博主所在制作组'); # 一对多问题
            $table->string('stars', 50)->comment('目标艺人'); # 一对多问题
            $table->string('star_ids', 50)->comment('目标艺人id'); # 一对多问题
            $table->string('bloggers', 50)->comment('目标博主'); # 一对多问题
            $table->string('blogger_ids', 50)->comment('目标博主id'); # 一对多问题
            $table->string('producer', 50)->comment('制作人'); # 一对多问题
            $table->string('producer_id', 50)->comment('制作人id'); # 一对多问题
            $table->string('broker', 50)->comment('经纪人'); # 一对多问题
            $table->string('broker_id', 50)->comment('经纪人id'); # 一对多问题

            $table->string('client', 80)->comment('关联公司');
            $table->unsignedInteger('last_follow_up_user_id')->comment('最近更新人id');
            $table->string('last_follow_up_user_name', 10)->comment('最近更新人');
            $table->string('last_follow_up_at', 19)->comment('最近跟进时间');
            $table->string('last_updated_at', 19)->comment('最近更新时间');
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
        Schema::dropIfExists('project_implode');
    }
}
