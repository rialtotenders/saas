<?php

namespace Perevorot\Users\Classes\Validators;

use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class TenderStepSixValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'procuringEntity.contactPoint.email' => 'required',
        'procuringEntity.contactPoint.name' => 'required',
        'procuringEntity.contactPoint.telephone' => 'required',
        'procuringEntity.contactPoint.faxNumber' => 'required',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'procuringEntity.contactPoint.email.required' => '',
        'procuringEntity.contactPoint.name.required' => '',
        'procuringEntity.contactPoint.telephone.required' => '',
        'procuringEntity.contactPoint.faxNumber.required' => '',
    ];

    public static $messageCodes = [
        'tender.procuringEntity.contactPoint.email.required' ,
        'tender.procuringEntity.contactPoint.name.required' ,
        'tender.procuringEntity.contactPoint.telephone.required' ,
        'tender.procuringEntity.contactPoint.faxNumber.required' ,
    ];
}
