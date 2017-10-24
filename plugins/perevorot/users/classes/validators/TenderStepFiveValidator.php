<?php

namespace Perevorot\Users\Classes\Validators;

use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class TenderStepFiveValidator extends ValidatorInterface
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
        'tender.items.deliveryAddress.locality.required'  ,
        'tender.items.deliveryAddress.streetAddress.required' ,
        'tender.items.deliveryAddress.region.required' ,
        'tender.items.deliveryAddress.postalCode.required' ,
        'tender.items.deliveryAddress.postalCode.with_prefix' ,
        'tender.items.deliveryAddress.postalCode.digits' ,
        'tender.items.deliveryDate.endDate.required' ,
        'tender.items.deliveryDate.endDate.greater_than_field' ,
        'tender.items.deliveryDate.startDate.required' ,
        'tender.items.deliveryDate.startDate.less_than_field' ,
        'tender.items.deliveryDate.startDate.greater_than_field' ,
        'tender.items.unit.code.required' ,
        'tender.items.quantity.required' ,
        'tender.items.quantity.numeric' ,
        'tender.items.classification.id.required' ,
        'tender.items.description.required' ,
        'tender.lots.title.required' ,
        'tender.lots.description.required' ,
        'tender.lots.features.title.required' ,
        'tender.lots.features.title.need_zero_enum' ,
        'tender.lots.features.enum.title.required' ,
        'tender.lots.features.enum.value.required' ,
        'tender.lots.features.enum.value.numeric',
        'tender.lots.features.enum.value.check_lot_enum_value',
        'tender.lots.items.deliveryAddress.locality.required'  ,
        'tender.lots.items.deliveryAddress.streetAddress.required' ,
        'tender.lots.items.deliveryAddress.region.required' ,
        'tender.lots.items.deliveryAddress.postalCode.required' ,
        'tender.lots.items.deliveryAddress.postalCode.digits' ,
        'tender.lots.items.deliveryAddress.postalCode.with_prefix' ,
        'tender.lots.items.deliveryDate.endDate.required' ,
        'tender.lots.items.deliveryDate.endDate.greater_than_field' ,
        'tender.lots.items.deliveryDate.startDate.required' ,
        'tender.lots.items.deliveryDate.startDate.less_than_field' ,
        'tender.lots.items.deliveryDate.startDate.greater_than_field' ,
        'tender.lots.items.unit.code.required' ,
        'tender.lots.items.quantity.required' ,
        'tender.lots.items.quantity.numeric' ,
        'tender.lots.items.classification.id.required' ,
        'tender.lots.items.description.required' ,
        'tender.lots.value.amount.required'        ,
        'tender.lots.value.amount.between',
        'tender.lots.value.amount.numeric'       ,
        'tender.lots.minimalStep.amount.required'    ,
        'tender.lots.minimalStep.amount.numeric'     ,
        'tender.lots.minimalStep.amount.between'  ,
        'tender.lots.guarantee.amount.required'     ,
        'tender.lots.guarantee.amount.numeric'    ,
    ];
}
