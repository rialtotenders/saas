<?php

namespace Perevorot\Users\Classes;

use Perevorot\Users\Classes\Validators\ProfileValidation;
use Perevorot\Users\Classes\Validators\StepOneValidator;
use Perevorot\Users\Classes\Validators\StepThreeValidator;
use Perevorot\Users\Classes\Validators\StepTwoValidator;
use Perevorot\Users\Classes\Validators\ValidatorInterface;

/**
 * Class RegistrationFactory
 * @package Perevorot\Users\Classes
 */
class ValidationFactory
{
    /**
     * @param array $data
     * @param $step
     * @return ValidatorInterface
     * @throws \Exception
     */
    public static function make(array $data, $step)
    {
        switch ($step) {
            case 1:
                return new StepOneValidator($data);
                break;

            case 2:
                return new StepTwoValidator($data);
                break;

            case 3:
                return new StepThreeValidator($data);
                break;

            case 4:
                return new ProfileValidation($data);
                break;

            default:
                throw new \Exception('Current step don\'t found');
        }
    }
}
