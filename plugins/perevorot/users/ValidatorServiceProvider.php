<?php namespace Perevorot\Users;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Models\TimingControl;
use Config;

class ValidatorServiceProvider extends ServiceProvider
{

    public function register()
    {
        // TODO: Implement register() method.
    }

    public function boot()
    {
        Validator::extend('check_minimal_days', function($attribute, $value, $parameters, $validator) {

            if($timing = TimingControl::getTiming(Session::get('tender.id'))) {

                $weekends = Config::get('weekend_dates');
                $now = Carbon::now();
                $selected_dt = Carbon::createFromTimestamp(strtotime($value));
                $days = $now->diffInDays($selected_dt);

                if($_days = $now->diffInWeekendDays($selected_dt)) {
                    $days -= $_days;
                }

                $__days = $now->diffInDaysFiltered(function(Carbon $date) use($weekends) {
                    return in_array($date->format('d.m.Y'), $weekends);
                }, $selected_dt);

                if($__days) {
                    $days -= $__days;
                }

                $field = $parameters[0]."_days";

                return $days >= $timing->$field;
            } else {
                return true;
            }

        });

        Validator::replacer('check_minimal_days', function($message, $attribute, $rule, $parameters) {
            return str_replace(':field', $parameters[0], $message);
        });

        Validator::extend('custom_required_with', function($attribute, $value, $parameters, $validator) {

            $data = $validator->getData();

            if($data[$parameters[0]] == $parameters[1]) {
                return true;
            }

            return false;
        });

        Validator::extend('with_prefix', function($attribute, $value, $parameters, $validator) {

            if(stripos($value, $parameters[0]) !== 0) {
                return false;
            }

            if(strlen($value) != (strlen($parameters[0]) + $parameters[1])) {
                return false;
            }

            return true;
        });

        Validator::extend('greater_than_current', function($attribute, $value, $parameters, $validator) {

            $data = $validator->getData();

            return Carbon::createFromFormat('Y-n', ($data['budget']['year'].'-'.$value))->timestamp > Carbon::now()->timestamp;

        });

        Validator::extend('greater_than_now', function($attribute, $value, $parameters, $validator) {

            if(!isset($parameters[1])) {
                if (isset($parameters[0])) {
                    return strtotime($value) > Carbon::now()->addMinutes($parameters[0])->timestamp;
                } else {
                    return strtotime($value) > Carbon::now()->timestamp;
                }
            } else {
                if($parameters[1] == 'days') {
                    return strtotime($value) > Carbon::now()->addDays($parameters[0])->timestamp;
                }
            }

            return true;
        });

        Validator::replacer('greater_than_now', function($message, $attribute, $rule, $parameters) {
            return str_replace(':field', null, $message);
        });

        Validator::extend('need_zero_enum', function($attribute, $value, $parameters, $validator) {

            $data = $validator->getData();

            if(stripos($attribute, 'lot') !== FALSE) {
                $f_data = explode('.', $attribute);
                $feature = $data[$f_data[0]][$f_data[1]]['features'][$f_data[3]];
            }
            elseif(stripos($attribute, 'lot') === FALSE) {
                $f_data = explode('.', $attribute);
                $feature = $data['features'][$f_data[1]];
            }

            $feature_isset_zero_enum = array_first($feature['enum'], function($_enum, $ekey) {
                return $_enum['value'] == 0;
            });
            $not_only_zero = array_first($feature['enum'], function($_enum, $ekey) {
                return $_enum['value'] > 0;
            });

            /*if($only_zero || !$feature_isset_zero_enum) {
                return false;
            }*/

            return $not_only_zero && $feature_isset_zero_enum;
        });

        Validator::extend('check_tender_enum_value', function($attribute, $value, $parameters, $validator) {

            $data = $validator->getData();
            $uniq_values = [];
            $max_val_array = [];

            if(isset($data['features'])) {
                $features = $data['features'];
            }

            foreach($features as $feature) {

                $max_val = 0;

                foreach($feature['enum'] as $enum) {
                    $_enum_value = $enum['value'];

                    if ($max_val < $_enum_value) {
                        $max_val = $_enum_value;
                    }

                    if($_enum_value > 0) {
                        if (!in_array($_enum_value, $uniq_values)) {
                            $uniq_values[] = $_enum_value;
                        } else {
                            return false;
                        }
                    }
                }

                $max_val_array[] = $max_val;
            }

            return (float)array_sum($max_val_array) <= 30;
        });

        Validator::extend('check_lot_enum_value', function($attribute, $value, $parameters, $validator) {

            $data = $validator->getData();
            $uniq_values = [];
            $max_val_array = [];

            if(isset($data['lots'])) {
                $min_field = $parameters[0];
                $names = explode(".", $min_field);

                if(isset($data[$names[0]][$names[1]]['features'])) {
                    $features = $data[$names[0]][$names[1]]['features'];
                }
            }

            foreach($features as $feature) {

                $max_val = 0;

                foreach($feature['enum'] as $enum) {
                    $_enum_value = $enum['value'];

                    if($max_val < $_enum_value) {
                        $max_val = $_enum_value;
                    }

                    if($_enum_value > 0) {
                        if (!in_array($_enum_value, $uniq_values)) {
                            $uniq_values[] = $_enum_value;
                        } else {
                            return false;
                        }
                    }
                }

                $max_val_array[] = $max_val;
            }

            if((float)array_sum($max_val_array) > 30) {
                return false;
            }

            $tender = Tender::find(Session::get('tender.id'));

            if(!$tender) {
                return null;
            }

            $tender = $tender->getJson();
            $max_val_array = [];

            if(isset($tender->features)) {
                $_tender_f = (array)$tender->features;

                foreach($_tender_f as $feature) {

                    $feature = (array)$feature;
                    $max_val = 0;

                    foreach($feature['enum'] as $enum) {
                        $enum = (array)$enum;
                        $_enum_value = $enum['value'];

                        if ($max_val < $_enum_value) {
                            $max_val = $_enum_value;
                        }
                    }

                    $max_val_array[] = $max_val;
                }
            } else {
                return true;
            }

            $max_val = 0;

            foreach ($features AS $feature) {

                $feature = (array)$feature;

                foreach ($feature['enum'] as $enum) {

                    $enum = (array)$enum;
                    $_enum_value = $enum['value'];

                    if ($max_val < $_enum_value) {
                        $max_val = $_enum_value;
                    }
                }

                $max_val_array[] = $max_val;
            }

            return (float)array_sum($max_val_array) <= 30;
        });

        Validator::extend('greater_than_field', function($attribute, $value, $parameters, $validator) {

            if(!isset($parameters[1])) {
                $min_field = $parameters[0];
                $data = $validator->getData();
                $min_value = array_get($data, $min_field);
            } else {
                $min_field = $parameters[1];
                $names = explode(".", $min_field);

                if($parameters[0] == 'tender') {
                    $tender = Tender::find(Session::get('tender.id'));
                } elseif($parameters[0] == 'plan') {
                    $tender = Plan::find(Session::get('plan.id'));
                    $data = $validator->getData();

                    if(stripos($parameters[1], 'deliveryDate.startDate') !== FALSE) {
                        $value = Carbon::createFromFormat('Y-n-d H:i:s', "{$data['budget']['year']}-{$value}-01 00:00:00")->toAtomString();
                    }
                }

                $data = (array)$tender->getJson();

                if(isset($names[0])) {
                    $data[$names[0]] = (array)$data[$names[0]];

                    if($tender instanceof Plan) {
                        if (isset($names[1]) && isset($data[$names[0]][$names[1]])) {
                            $data[$names[0]][$names[1]] = (array)$data[$names[0]][$names[1]];
                        }
                        if($parameters[0] == 'plan' && stripos($parameters[1], 'deliveryDate.startDate') !== FALSE) {
                            if (isset($names[2]) && isset($data[$names[0]][$names[1]][$names[2]])) {
                                $data[$names[0]][$names[1]][$names[2]] = (array)$data[$names[0]][$names[1]][$names[2]];
                            }
                        }
                    }
                }

                $min_value = array_get($data, $min_field);

                if($min_value && $parameters[0] == 'plan' && stripos($parameters[1], 'deliveryDate.startDate') !== FALSE) {
                    $t = explode('.', $min_value);
                    $min_value = Carbon::createFromFormat('Y-m-d H:i:s', "{$t[2]}-{$t[1]}-01 00:00:00")->toAtomString();
                    return strtotime($value) <= strtotime($min_value);
                }
            }

            return strtotime($value) > strtotime($min_value);
        });

        Validator::replacer('greater_than_field', function($message, $attribute, $rule, $parameters) {
            return str_replace(':field', isset($parameters[1]) ? $parameters[1] : $parameters[0], $message);
        });

        Validator::extend('less_than_field', function($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = array_get($data, $min_field);

            return strtotime($value) < strtotime($min_value);
        });

        Validator::replacer('less_than_field', function($message, $attribute, $rule, $parameters) {
            return str_replace(':field', $parameters[0], $message);
        });
    }
}
