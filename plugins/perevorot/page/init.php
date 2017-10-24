<?php
    use System\Models\EventLog;
    use Perevorot\Form\Classes\Api;
    
    if(env('APP_DEBUG', false) && !Request::server('HTTP_X_REQUESTED_WITH'))
        return;

    App::error(function($exception) {
        $message=Api::cleanAccToken($exception->getMessage());

        Log::error($message);
        EventLog::add($message, 'error');

        if(stripos($message, 'OCTOBER') === FALSE) {
            $output_message = env('APP_DEBUG', false) ? $message : 'Error: свяжитесь с администратором';
        } else {
            $output_message = $message;
        }
        
        if(!substr(\Request::path(), 0, 8)=='backend/'){
            return Request::server('HTTP_X_REQUESTED_WITH') ? json_encode([
                'error'=>true,
                'message'=>$output_message
            ]) : $output_message;
        }
    });

    App::fatal(function($exception) {
        $message=Api::cleanAccToken($exception->getMessage());

        Log::critical($message);
        EventLog::add($message, 'critical');

        if(stripos($message, 'OCTOBER') === FALSE) {
            $output_message = env('APP_DEBUG', false) ? $message : 'Error: свяжитесь с администратором';
        } else {
            $output_message = $message;
        }
        
        if(!substr(\Request::path(), 0, 8)=='backend/'){
            return Request::server('HTTP_X_REQUESTED_WITH') ? json_encode([
                'error'=>true,
                'message'=>$output_message
            ]) : $output_message;
        }
    });