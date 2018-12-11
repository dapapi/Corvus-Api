<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFilterFieldsTableAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('filter_fields', function (Blueprint $table) {
            $table->string('company')->after('department_id');
        });
        Schema::table('filter_joins', function (Blueprint $table) {
            $table->string('company')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('filter_fields', function (Blueprint $table) {
            $table->dropColumn('company');
        });
        Schema::table('filter_joins', function (Blueprint $table) {
            $table->dropColumn('company');
        });
    }
}
