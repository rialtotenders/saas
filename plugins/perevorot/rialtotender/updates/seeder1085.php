<?php namespace Perevorot\Rialtotender\Updates;

use Seeder;
use DB;

class Seeder1085 extends Seeder
{
    public function run()
    {
        $messages=DB::table('rainlab_translate_messages')->get();
        
        foreach($messages as $message)
        {
            $data=json_decode($message->message_data, true);
            
            foreach($data as $k=>$d)
            {
                if(empty($d))
                    unset($data[$k]);
            }
            
            if(!empty($data))
            {
                DB::table('rainlab_translate_messages')->where('id', '=', $message->id)->update([
                    'message_data'=>json_encode($data)
                ]);
            }
        }
    }
}