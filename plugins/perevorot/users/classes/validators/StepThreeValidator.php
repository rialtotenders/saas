<?php

namespace Perevorot\Users\Classes\Validators;

use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class StepThreeValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'contact_fio'          => 'required',
        'contact_position'     => 'required',
        'contact_email'        => 'required|email',
        'contact_office_phone' => 'required',
        'contact_mobile_phone' => 'required',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'contact_fio.required'          => '',
        'contact_position.required'     => '',
        'contact_email.required'        => '',
        'contact_email.email'           => '',
        'contact_office_phone.required' => '',
        'contact_mobile_phone.required' => '',
    ];

    public static $messageCodes = [
        'registration.contact_fio.required'  ,
        'registration.contact_position.required'  ,
        'registration.contact_email.required'  ,
        'registration.contact_email.email'  ,
        'registration.contact_office_phone.required'  ,
        'registration.contact_mobile_phone.required'  ,
    ];
}
