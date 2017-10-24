<?php

namespace Perevorot\Users\Classes\Validators;
use RainLab\Translate\Models\Message;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class PlanStepOneValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'budget.notes'         => 'required',
        'budget.description'      => 'required|max:255',
        'budget.year'        => 'required',
        'budget.amount'     => 'required|numeric|between:0,1000000000',
        'budget.currency'     => 'required',
        //'tender.tenderPeriod.startDate'     => 'required',
        'classification.id' => 'required',
        'month' => 'required|greater_than_current',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'tender.procurementMethodType' => '',
        'tender.procurementMethod' => '',
        'budget.year.required' => '',
        'budget.description.required' => '',
        'budget.description.max'     => '',
        'budget.notes.required' => '',
        'budget.amount.required' => '',
        'budget.amount.between' => '',
        'budget.currency.required' => '',
        'budget.amount.numeric' => '',
        'classification.id.required'=> '',
        'tender.tenderPeriod.startDate.required'=> '',
        'month.required'=> '',
        'month.greater_than_current'=> '',
        'month.greater_than_field'=> '',
        'month.less_than_field'=> '',
    ];

    public static $messageCodes = [
        'plan.tender.procurementMethodType' ,
        'plan.tender.procurementMethod' ,
        'plan.budget.year.required',
        'plan.budget.description.required',
        'plan.budget.description.max',
        'plan.budget.notes.required',
        'plan.budget.amount.required',
        'plan.budget.amount.between',
        'plan.budget.currency.required',
        'plan.budget.amount.numeric'   ,
        'plan.classification.id.required',
        'plan.tender.tenderPeriod.startDate.required'    ,
        'plan.month.required'  ,
        'plan.month.greater_than_current'  ,
        'plan.month.greater_than_field',
        'plan.month.less_than_field',
        'plan.tender.tenderPeriod.startDate.greater_than_field'  ,
    ];
}
