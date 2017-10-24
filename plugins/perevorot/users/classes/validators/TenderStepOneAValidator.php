<?php

namespace Perevorot\Users\Classes\Validators;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class TenderStepOneAValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
    ];

    /**
     * @var array
     */
    public $customMessages = [
    ];

    public static $messageCodes = [
        'tender.features.title.required',
        'tender.features.enum.title.required_with',
        'tender.features.enum.value.required_with',
        'tender.features.enum.value.numeric',
        'tender.features.enum.value.check_tender_enum_value',
        'tender.features.need_zero_enum',
    ];
}
