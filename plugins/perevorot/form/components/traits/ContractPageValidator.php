<?php

namespace Perevorot\Form\Components\Traits;

trait ContractPageValidator
{
    protected $rules = [
        'terminationDetails' => 'required',
        'amountPaid.amount' => 'required|numeric',
    ];

    protected $customMessages = [
        'terminationDetails.required' => '',
        'amountPaid.amount.required' => '',
        'amountPaid.amount.numeric' => '',
    ];

    public static $messageCodes = [
        'contract.amountPaid.amount.numeric',
        'contract.amountPaid.amount.required',
        'contract.terminationDetails.required',
    ];
}
