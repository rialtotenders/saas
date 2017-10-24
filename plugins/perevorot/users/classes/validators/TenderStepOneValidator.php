<?php

namespace Perevorot\Users\Classes\Validators;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class TenderStepOneValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'title'         => 'required|max:255',
        'description'         => 'required',
        'cpv'        => 'required',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'procurementMethodType.required' => '',
        'procurementMethodType.custom_required_with' => '',
        'procurementMethod.required' => '',
        'title.required' => '' ,
        'title.max'   => '',
        'description.required' => '',
        'cpv.required' => '',
    ];

    public static $messageCodes = [
        'tender.procurementMethodType.required' ,
        'tender.procurementMethodType.custom_required_with' ,
        'tender.procurementMethod.required' ,
        'tender.title.required'  ,
        'tender.title.max'   ,
        'tender.description.required' ,
        'tender.cpv.required' ,
    ];
}
