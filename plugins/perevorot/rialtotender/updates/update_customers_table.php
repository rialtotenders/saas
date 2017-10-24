<?php
namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateCustomersTable extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_customers', function($table)
        {
            $table->string('edrpou');
        });
    }

    public function down()
    {
        Schema::table('perevorot_rialtotender_customers', function($table)
        {
            $table->dropColumn('edrpou');
        });
    }
}
