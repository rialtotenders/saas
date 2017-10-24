<?php

namespace Perevorot\Form\Components\Traits;

trait ContractsValidator
{
    protected $rules = [
        'title' => 'required',
        'period.endDate' => 'required|greater_than_field:period.startDate',
        'period.startDate' => 'required|greater_than_field:dateSigned|less_than_field:period.endDate',
        'dateSigned' => 'required|same_day:complaintPeriod.endDate',
        'contractNumber' => 'required',
    ];

    protected $customMessages = [
        'title.required' => '',
        'period.endDate.required' => '',
        'period.endDate.greater_than_field' => '',
        'period.startDate.required' => '',
        'period.startDate.less_than_field' => '',
        'period.startDate.greater_than_field' => '',
        'dateSigned.required' => '',
        'dateSigned.same_day' => '',
        'dateSigned.greater_than_now' => '',
        'dateSigned.greater_than_field' => '',
        'contractNumber.required' => '',
        'complaintPeriod.greater_than_date' => '',
    ];

    public static $messageCodes = [
        'contract.title.required',
        'contract.period.endDate.required',
        'contract.period.endDate.greater_than_field',
        'contract.period.startDate.required',
        'contract.period.startDate.less_than_field',
        'contract.period.startDate.greater_than_field',
        'contract.dateSigned.required' ,
        'contract.dateSigned.same_day' ,
        'contract.dateSigned.less_than_now' ,
        'contract.dateSigned.greater_than_field' ,
        'contract.dateSigned.greater_than_date' ,
        'contract.contractNumber.required',
        'contract.complaintPeriod.greater_than_date',
    ];
}
