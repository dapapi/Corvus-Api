<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalFormFixedParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_form_fixed_participants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('form_id')->comment('审批表单id');
            $table->unsignedInteger('notice_id')->comment('知会人id');
            $table->unsignedInteger('notice_type')->comment('知会人类型，对应字典表id:245、246、247');
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
        Schema::dropIfExists('approval_form_fixed_participants');
    }
}
