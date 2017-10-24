<?php namespace Perevorot\Rialtotender\Updates;

use Seeder;
use DB;

class Seeder1031 extends Seeder
{
    public function run()
    {
        DB::table('perevorot_rialtotender_reg_tips')->truncate();        
        DB::table('perevorot_rialtotender_reg_tips')->insert([
            'header1' => '',
        ]);
    }
}