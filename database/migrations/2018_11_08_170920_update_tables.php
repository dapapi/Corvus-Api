<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();
        try {
//
//            Schema::table('clients', function (Blueprint $table) {
//                $table->dropForeign();
//                $table->dropColumn('industry_id');
//                $table->dropColumn('industry');
//            });

//            Schema::table('trails', function (Blueprint $table) {
//                $table->unsignedInteger('industry_id')->nullable()->after('principal_id');
//                $table->foreign('industry_id')->references('id')->on('industries');
//            });
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
        }
        DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::beginTransaction();
        try {
//            Schema::table('clients', function (Blueprint $table) {
//                $table->unsignedInteger('industry_id');
//                $table->string('industry');
//                $table->foreign('industry_id')->references('id')->on('industries');
//            });
//
//            Schema::table('trails', function (Blueprint $table) {
//                $table->dropForeign(['industry_id']);
//                $table->dropColumn('industry_id');
//            });
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);
        }
        DB::commit();

    }
}
