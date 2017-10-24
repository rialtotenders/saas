<?php namespace Perevorot\Form;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class ValidatorServiceProvider extends ServiceProvider
{

    public function register()
    {
        // TODO: Implement register() method.
    }

    public function boot()
    {
        Validator::extend('same_day', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = array_get($data, $min_field);
            $_v = Carbon::createFromFormat('d.m.Y', $value)->timestamp;

            return $_v <= Carbon::now()->timestamp && $_v > Carbon::createFromTimestamp(strtotime($min_value))->timestamp;
        });

        Validator::extend('greater_than_date', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = array_get($data, $min_field);

            return Carbon::createFromFormat('d.m.Y', $value)->timestamp > Carbon::createFromTimestamp(strtotime($min_value))->timestamp;
        });

        Validator::extend('less_than_now', function ($attribute, $value, $parameters, $validator) {

            if (isset($parameters[0])) {
                return strtotime($value) < Carbon::now()->addMinutes($parameters[0])->timestamp;
            } else {
                return strtotime($value) < Carbon::now()->timestamp;
            }

        });
    }
}
