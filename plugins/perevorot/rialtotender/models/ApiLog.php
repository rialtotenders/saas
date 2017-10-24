<?php namespace Perevorot\Rialtotender\Models;

use Model;
use Perevorot\Users\Facades\Auth;
use Request;
use Carbon\Carbon;
use Perevorot\Form\Classes\Api;

/**
 * Model
 */
class ApiLog extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    public $table = 'perevorot_rialtotender_apilog';

    public function getUserNameAttribute($value)
    {
        return  $this->user ? $this->user->username : null;
    }

    /**
     * @param $method
     * @param $url
     * @param $data
     * @param $xRequestId
     * @return bool
     */
    public static function saveData($action, $method, $url, $data, $xRequestId, $response='', $tender = false, $is_error=false)
    {
        if($method && $url)
        {
            $api_log = new self();
            $user=Auth::getUser();

            $api_log->action = $action;
            $api_log->ip = Request::ip();
            $api_log->method = $method;

            if($tender) {
                if($tender instanceof Tender) {
                    $api_log->tender_id = $tender->tender_system_id;
                    $api_log->format_tender_id = $tender->tender_id;
                } elseif($tender instanceof Plan) {
                    $api_log->tender_id = $tender->plan_system_id;
                    $api_log->format_tender_id = $tender->plan_id;
                } else {
                    $api_log->tender_id = $tender->id;
                    $api_log->format_tender_id = isset($tender->tenderID) ? $tender->tenderID : $tender->planID;
                }
            } else {
                $api_log->tender_id = '-';
                $api_log->format_tender_id = '-';
            }

            $api_log->user_id = !empty($user->id) ? $user->id : 0;
            $api_log->response = Api::cleanAccToken(is_object($response) ? $response->getMessage() : $response);
            $api_log->url = Api::cleanAccToken($url);
            $api_log->data = Api::cleanAccToken(is_array($data) || is_object($data) ? json_encode($data) : $data);
            $api_log->created_at = Carbon::now();
            $api_log->x_request_id = $xRequestId ? $xRequestId : null;

            if(is_object($response) && $is_error){
                $_response =json_decode($response->getResponse()->getBody());
    
                if(isset($_response->errors) && !empty($_response->errors)) {
                    $api_log->error = Api::cleanAccToken(json_encode($_response->errors));
                }
            } elseif($is_error) {
                $api_log->error = Api::cleanAccToken($response);
            }

            \IntegerLog::{$is_error?'error':'info'}('api.'.$api_log->action, [
                'url'=>$api_log->url,
                'method'=>$api_log->method,
                'tender_id'=>$api_log->tender_id,
                'x_request_id'=>$api_log->x_request_id,
                'error' => is_object($response) ? Api::cleanAccToken($response->getResponse()->getBody()) : Api::cleanAccToken($response)
            ]);
        
            if($api_log->save()) {
                return true;
            }
        }

        return false;
    }

    public static function saveDataError($action, $method, $url, $data, $xRequestId, $response='', $tender = false)
    {
        return self::saveData($action, $method, $url, $data, $xRequestId, $response, $tender, true);
    }


    public $hasOne = [
        'user' => ['Rainlab\User\Models\User', 'key' => 'id', 'otherKey' => 'user_id']
    ];
}