<?php namespace Perevorot\Rialtotender\Classes;

use Carbon\Carbon;
use Perevorot\Users\Facades\Auth;
use Log;

class IntegerLog
{
    private static $user;
    
    public static function info($action, $message='')
    {
        Log::info('['.self::user().'] '.$action.($message && !is_array($message) ? ': '.$message : ''), (is_array($message) ? $message : []));
    }

    public static function error($action, $message='')
    {
        $_message = $message;

        if(is_array($_message)) {
            $_message['dt'] = Carbon::now();
            $_message = array_map(function($v, $key) {
               return "$key=$v";
            }, array_values($_message), array_keys($_message));
            $_message = implode("\n", $_message);
        }

        $msg = "Error on <".env('APP_URL')."|".env('APP_URL')."> in {$action}";
        $api = new SlackApi();
        $api->sendHook($msg, $_message);

        Log::error('['.self::user().'] '.$action.($message && !is_array($message) ? ': '.$message : ''), (is_array($message) ? $message : []));
    }
    
    private static function user()
    {
        if(is_null(self::$user))
            self::$user=Auth::getUser();
            
        return !empty(self::$user->id) ? self::$user->id : 'noauth';
    }
}
