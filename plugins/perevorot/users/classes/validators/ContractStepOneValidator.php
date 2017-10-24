<?php

namespace Perevorot\Users\Classes\Validators;


class ContractStepOneValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'change.contractNumber'  => 'required',
        'change.dateSigned' => 'required|same_day:last_change.dateSigned',
        'change.rationale' => 'required',
        'change.rationaleTypes' => 'required',
        'period.endDate' => 'required|greater_than_field:period.startDate',
        'period.startDate'  => 'required|greater_than_field:change.dateSigned|less_than_field:period.endDate',
        'value.amount' => 'required|numeric',
        //'value.currency' => 'required',
        //'value.valueAddedTaxIncluded' => 'required',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'change.contractNumber.required' => '' ,
        'change.dateSigned.required'   => '',
        'change.dateSigned.same_day'   => '',
        'change.rationale.required'   => '',
        'change.rationaleTypes.required'   => '',
        'period.endDate.required' => '',
        'period.endDate.greater_than_field' => '',
        'period.startDate.required' => '',
        'period.startDate.less_than_field' => '',
        'period.startDate.greater_than_field' => '',
        'value.amount.required' => '',
        'value.amount.numeric' => '',
        'value.currency.required' => '',
        'value.valueAddedTaxIncluded.required' => '',
    ];

    public static $messageCodes = [
        'contract.value.amount.required',
        'contract.value.amount.numeric',
        'contract.change.contractNumber.required' ,
        'contract.change.rationale.required' ,
        'contract.change.rationaleTypes.required' ,
        'contract.change.dateSigned.required' ,
        'contract.change.dateSigned.same_day' ,
        'contract.period.endDate.required',
        'contract.period.endDate.greater_than_field',
        'contract.period.startDate.required',
        'contract.period.startDate.less_than_field',
        'contract.period.startDate.greater_than_field',
        'contract.value.currency.required' ,
        'contract.value.valueAddedTaxIncluded.required',
    ];
}
