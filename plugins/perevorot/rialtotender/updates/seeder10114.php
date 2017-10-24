<?php namespace Perevorot\Rialtotender\Updates;

use DB;
use Perevorot\Users\Models\User;
use Seeder;

class Seeder10114 extends Seeder
{
    public function run()
    {
        $users = DB::table('users')->get();
        
        foreach ($users AS $user) {
            if($_user = User::find($user->id)) {

                $data = json_decode($user->data);

                if(isset($data->company_name)) {
                    $_user->company_name = $data->company_name;
                    $_user->save();
                }
            }
        }
    }
}