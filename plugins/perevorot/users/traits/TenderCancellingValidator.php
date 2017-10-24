<?php

namespace Perevorot\Users\Traits;

trait TenderCancellingValidator
{
    protected $rules = [
        'reason' => 'required',
    ];

    protected $customMessages = [
        'reason.required' => '',
    ];

    public static $messageCodes = [
        'tender_cancelling.reason.required',
    ];
}
