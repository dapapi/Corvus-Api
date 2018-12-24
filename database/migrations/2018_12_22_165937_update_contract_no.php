<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateContractNo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_no', function (Blueprint $table) {
            $table->dropColumn("company_name");
            $table->dropColumn("signing_subject");
            $table->dropColumn("address_code");
            $table->dropColumn("type");
            $table->dropColumn("year");
            $table->string("key");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_no', function (Blueprint $table) {
            //
        });
    }
}
