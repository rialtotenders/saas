<?php

namespace Perevorot\Users\Classes\Validators;

use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

class ContractStepThreeValidator extends ValidatorInterface
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
        'contract.items.deliveryAddress.locality.required'  ,
        'contract.items.deliveryAddress.streetAddress.required' ,
        'contract.items.deliveryAddress.region.required' ,
        'contract.items.deliveryAddress.postalCode.required' ,
        'contract.items.unit.code.required' ,
        'contract.items.quantity.required' ,
        'contract.items.quantity.numeric' ,
        'contract.items.classification.id.required' ,
        'contract.items.classification.scheme.required' ,
        'contract.items.classification.description.required' ,
        'contract.items.description.required' ,
    ];
}
