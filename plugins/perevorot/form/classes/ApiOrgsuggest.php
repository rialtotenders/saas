<?php

namespace Perevorot\Form\Classes;

use App;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Traits\UserSetting;

class ApiOrgsuggest
{
    use UserSetting;

    public static function getCustomers($data = [])
    {
        $org = new ApiOrgsuggest();
        $user_mode = $org->checkUserMode(Auth::getUser(), (!empty(post('is_gov')) && post('is_gov') > 0 ? true : false));

        foreach($data as $k => $v) {
            $params[$v->procuringEntity_identifier_id] = "edrpou={$v->procuringEntity_identifier_id}";
        }

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, env('API_'.$user_mode.'ORGSUGGEST').'?'.implode('&', $params));

        if(env('API_'.$user_mode.'LOGIN') && env('API_'.$user_mode.'PASSWORD')){
            curl_setopt($ch, CURLOPT_USERPWD, env('API_'.$user_mode.'LOGIN') . ":" . env('API_'.$user_mode.'PASSWORD'));
        }

        $in=curl_exec($ch);

        curl_close($ch);

        if($ch){
            $in=json_decode($in);

            if(!empty($in->items)){

                foreach($in->items as $one) {
                    $params[$one->edrpou] = $one->short ? $one->short : $one->name;
                }
            }
        }

        return $params;
    }

    public static function get()
    {
        if(empty(post('query')))
            return [];

        $org = new ApiOrgsuggest();
        $user_mode = $org->checkUserMode(Auth::getUser(), (!empty(post('is_gov')) && post('is_gov') > 0 ? true : false));

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, self::getUrl($user_mode));

        if(env('API_'.$user_mode.'LOGIN') && env('API_'.$user_mode.'PASSWORD')){
            curl_setopt($ch, CURLOPT_USERPWD, env('API_'.$user_mode.'LOGIN') . ":" . env('API_'.$user_mode.'PASSWORD'));
        }

        $in=curl_exec($ch);

        curl_close($ch);
        
        $out=[];

        if($ch){
            $in=json_decode($in);

            if(!empty($in->items)){
                foreach($in->items as $one){
                    array_push($out, [
                        'id' => $one->edrpou,
                        'name'=> $one->short ? $one->short : $one->name
                    ]);
                }
            }
        }

        return $out;
    }
    
    private static function getUrl($user_mode)
    {
        return env('API_'.$user_mode.'ORGSUGGEST').'?query='.mb_strtolower(urlencode(post('query')));
    }
}