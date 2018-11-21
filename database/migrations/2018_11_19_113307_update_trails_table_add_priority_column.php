<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTrailsTableAddPriorityColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->tinyInteger('priority')->default(1)->after('status');
            $table->tinyInteger('cooperation_type')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->dropColumn('priority');
            $table->dropColumn('cooperation_type');
        });
    }
}
