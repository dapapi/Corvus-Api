<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTrailStarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trail_star', function (Blueprint $table) {
            $table->string('starable_type')->after('star_id');
            $table->dropForeign(['star_id']);
            $table->renameColumn('star_id', 'starable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trail_star', function (Blueprint $table) {

        });
    }
}
