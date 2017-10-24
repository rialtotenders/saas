<?php namespace Perevorot\Rialtotender\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePerevorotRialtotenderFormMessages extends Migration
{
    public function up()
    {
        Schema::table('perevorot_rialtotender_form_messages', function($table)
        {
            $table->string('header1_ua')->nullable();
            $table->string('header2_ua')->nullable();
            $table->string('header3_ua')->nullable();
            $table->text('step1_ua')->nullable();
            $table->text('step2_ua')->nullable();
            $table->text('step3_ua')->nullable();
            $table->string('header1_en')->nullable();
            $table->string('header2_en')->nullable();
            $table->string('header3_en')->nullable();
            $table->text('step1_en')->nullable();
            $table->text('step2_en')->nullable();
            $table->text('step3_en')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('perevorot_rialtotender_form_messages', function($table)
        {
            $table->dropColumn('header1_ua');
            $table->dropColumn('header2_ua');
            $table->dropColumn('header3_ua');
            $table->dropColumn('step1_ua');
            $table->dropColumn('step2_ua');
            $table->dropColumn('step3_ua');
            $table->dropColumn('header1_en');
            $table->dropColumn('header2_en');
            $table->dropColumn('header3_en');
            $table->dropColumn('step1_en');
            $table->dropColumn('step2_en');
            $table->dropColumn('step3_en');
        });
    }
}
