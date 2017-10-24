<?php namespace Perevorot\Rialtotender\Updates;

use Seeder;
use DB;

class Seeder1029 extends Seeder
{
    public function run()
    {
        DB::table('perevorot_rialtotender_form_messages')->truncate();        
        DB::table('perevorot_rialtotender_form_messages')->insert([
            'header1_ua' => '',
        ]);
    }
}