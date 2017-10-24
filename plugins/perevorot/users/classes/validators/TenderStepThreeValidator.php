<?php

namespace Perevorot\Users\Classes\Validators;

use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class TenderStepThreeValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'value.currency'     => 'required',
        'value.amount'     => 'required|numeric|between:0,1000000000',
        'value.valueAddedTaxIncluded'        => 'required',
        'minimalStep.amount' => '',
        'guarantee.amount' => 'required|numeric',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'value.currency.required'          => '',
        'value.amount.required'          => '',
        'value.amount.between'          => '',
        'value.amount.numeric'          => '',
        'value.valueAddedTaxIncluded.required'          => '',
        'minimalStep.amount.required'          => '',
        'minimalStep.amount.numeric'          => '',
        'minimalStep.amount.between'          => '',
        'guarantee.amount.required'          => '',
        'guarantee.amount.numeric'          => '',
    ];

    public static $messageCodes = [
        'tender.value.currency.required'  ,
        'tender.value.amount.required'        ,
        'tender.value.amount.between',
        'tender.value.amount.numeric'       ,
        'tender.value.valueAddedTaxIncluded.required'   ,
        'tender.minimalStep.amount.required'    ,
        'tender.minimalStep.amount.numeric'     ,
        'tender.minimalStep.amount.between'  ,
        'tender.guarantee.amount.required'     ,
        'tender.guarantee.amount.numeric'    ,
    ];
}
