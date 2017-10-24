<?php

namespace Perevorot\Users\Classes\Validators;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class StepOneValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'company_name'         => 'required',
        'company_address'      => 'required',
        'company_index'        => 'required',//|digits:5',
        'company_city'         => 'required',
        'company_region'         => 'required',
        'company_country'      => 'required',
        'payer'                => 'required',
        'payer_code'           => 'required_if:payer,1',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'company_name.required'         => '',
        'company_address.required'      => '',
        'company_index.required'        => '',
        'company_city.required'         => '',
        'company_index.digits'          => '',
        'company_index.with_prefix'     => '',
        'company_country.required'      => '',
        'payer.required'                => '',
        'payer_code.required_if'        => '',
        'roles.required'                => '',
        'email.required'                => '',
        'company_region.required'      => '',
        'email.email'                => '',
        'password.between' => '',
        'password.confirmed' => '',
        'iban.alpha_num'                => '',
        'iban.min'                => '',
        'iban.max'                => '',
    ];

    public static $messageCodes = [
        'registration.company_name.required'  ,
        'registration.company_address.required'  ,
        'registration.company_index.required'  ,
        'registration.company_city.required'  ,
        'registration.company_index.digits'  ,
        'registration.company_index.with_prefix'  ,
        'registration.company_country.required'  ,
        'registration.payer.required'  ,
        'registration.payer_code.required_if'  ,
        'registration.roles.required'  ,
        'registration.email.required'  ,
        'registration.email.email'  ,
        'registration.company_region.required'  ,
        'registration.iban.alpha_num'  ,
        'registration.iban.min'  ,
        'registration.iban.max'  ,
    ];
}
