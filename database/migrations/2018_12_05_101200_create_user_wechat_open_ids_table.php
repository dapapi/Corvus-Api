<?php

use App\Models\UserWechatOpenId;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserWechatOpenIdsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('user_wechat_open_ids', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id');
            $table->string('open_id')->unique();
            $table->tinyInteger('type')->default(UserWechatOpenId::TYPE_APP);
            $table->unsignedInteger('user_wechat_info_id')->nullable();
            $table->foreign('user_wechat_info_id')
                ->references('id')
                ->on('user_wechat_infos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('user_wechat_open_ids');
    }
}
