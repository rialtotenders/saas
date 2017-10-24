<?php

namespace Perevorot\Users\Classes\Validators;

use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class TenderStepFourValidator extends ValidatorInterface
{

    /**
     * @var array
     */
    public $rules = [
        'enquiryPeriod.endDate'          => 'required|date|check_minimal_days:enquire|greater_than_now:15',
        'tenderPeriod.startDate'          => 'required|date|greater_than_field:enquiryPeriod.endDate',
        'tenderPeriod.endDate'          => 'required|date|check_minimal_days:tender|greater_than_field:tenderPeriod.startDate',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'enquiryPeriod.endDate.required'          => '',
        'enquiryPeriod.endDate.check_minimal_days'          => '',
        'tenderPeriod.endDate.check_minimal_days'          => '',
        'enquiryPeriod.endDate.greater_than_now'          => '',
        'enquiryPeriod.startDate.date'          => '',
        'enquiryPeriod.endDate.date'          => '',
        'enquiryPeriod.endDate.less_than_field'          => '',
        'enquiryPeriod.endDate.greater_than_field'          => '',
        'enquiryPeriod.startDate.less_than_field'          => '',
        'enquiryPeriod.startDate.greater_than_field'          => '',
        'tenderPeriod.startDate.required'          => '',
        'tenderPeriod.endDate.required'          => '',
        'tenderPeriod.endDate.greater_than_now'          => '',
        'tenderPeriod.startDate.required_with'          => '',
        'tenderPeriod.endDate.required_with'          => '',
        'tenderPeriod.startDate.date'          => '',
        'tenderPeriod.endDate.date'          => '',
        'tenderPeriod.startDate.less_than_field'          => '',
        'tenderPeriod.endDate.less_than_field'          => '',
        'tenderPeriod.startDate.greater_than_field'          => '',
        'tenderPeriod.endDate.greater_than_field'          => '',
    ];

    public static $messageCodes = [
        'tender.enquiryPeriod.endDate.required' ,
        'tender.enquiryPeriod.endDate.check_minimal_days' ,
        'tender.tenderPeriod.endDate.check_minimal_days' ,
        'tender.tenderPeriod.endDate.greater_than_now' ,
        'tender.enquiryPeriod.endDate.greater_than_now' ,
        'tender.enquiryPeriod.startDate.date'         ,
        'tender.enquiryPeriod.endDate.date'          ,
        'tender.enquiryPeriod.endDate.less_than_field'          ,
        'tender.enquiryPeriod.endDate.greater_than_field'          ,
        'tender.enquiryPeriod.startDate.less_than_field'          ,
        'tender.enquiryPeriod.startDate.greater_than_field'          ,
        'tender.tenderPeriod.startDate.required'          ,
        'tender.tenderPeriod.endDate.required'          ,
        'tender.tenderPeriod.startDate.required_with'          ,
        'tender.tenderPeriod.endDate.required_with'          ,
        'tender.tenderPeriod.startDate.date'          ,
        'tender.tenderPeriod.endDate.date'          ,
        'tender.tenderPeriod.startDate.less_than_field'          ,
        'tender.tenderPeriod.endDate.less_than_field'          ,
        'tender.tenderPeriod.startDate.greater_than_field'          ,
        'tender.tenderPeriod.endDate.greater_than_field'          ,
    ];
}
