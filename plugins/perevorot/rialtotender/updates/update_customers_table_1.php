<?php
namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateCustomersTable1 extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_customers', function($table)
        {
            $table->text('edrpou')->change();
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
