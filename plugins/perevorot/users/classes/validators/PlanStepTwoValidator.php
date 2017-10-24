<?php

namespace Perevorot\Users\Classes\Validators;

use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class PlanStepTwoValidator extends ValidatorInterface
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
        'plan.items.deliveryDate.endDate.required' ,
        'plan.items.deliveryDate.endDate.greater_than_field' ,
        'plan.items.deliveryDate.startDate.required' ,
        'plan.items.deliveryDate.startDate.less_than_field' ,
        'plan.items.deliveryDate.startDate.greater_than_field' ,
        'plan.items.unit.code.required' ,
        'plan.items.quantity.required' ,
        'plan.items.quantity.numeric' ,
        'plan.items.classification.id.required' ,
        'plan.items.description.required' ,
    ];
}
