<?php namespace Perevorot\Rialtotender\Updates;

use Seeder;
use DB;

class Seeder1083 extends Seeder
{
    public function run()
    {
        DB::table('perevorot_rialtotender_currencies')->update([
            'sort_order'=>DB::raw('id')
        ]);
    }
}