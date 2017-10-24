<?php

namespace Perevorot\Users\Classes\Validators;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class StepTwoValidator extends ValidatorInterface
{
    /**
     * @var array
     */
    public $rules = [
        'director_position'    => 'required',
        'director_fio'         => 'required',
        'director_document'    => 'required',
        'roles'                => 'required',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'director_position.required'    => '',
        'director_fio.required'         => '',
        'director_document.required'    => '',
    ];

    public static $messageCodes = [
        'registration.director_position.required'  ,
        'registration.director_fio.required'  ,
        'registration.director_document.required'  ,
    ];
}
