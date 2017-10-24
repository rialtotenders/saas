<?php

namespace Perevorot\Form\Components\Traits;

/**
 * Class ApplicationUpdateStepUtils
 * @package Perevorot\Form\Traits
 */
trait CustomValidatorMessages
{
    protected $rules = [
    ];

    protected $customMessages = [
        'price.required' => '',
        'price.between' => '',
        'price.numeric' => '',
        'price.max' => '',
        'feature_price.required' => '',
        'feature_price.between' => '',
        'feature_price.numeric' => '',
        'feature_price.max' => '',
    ];

    public static $messageCodes = [
        'application.price.required',
        'application.price.between' ,
        'application.price.numeric' ,
        'application.price.max' ,
        'application.feature_price.required' ,
        'application.feature_price.between' ,
        'application.feature_price.numeric' ,
        'application.feature_price.max' ,
        'application.lots.price.between' => '',
        'application.lots.price.numeric' => '',
        'application.lots.feature_price.between' => '',
        'application.lots.feature_price.numeric' => '',
    ];
}
