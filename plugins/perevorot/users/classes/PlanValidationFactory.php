<?php

namespace Perevorot\Users\Classes;

use Perevorot\Users\Classes\Validators\PlanStepOneValidator;
use Perevorot\Users\Classes\Validators\PlanStepTwoValidator;
use Perevorot\Users\Classes\Validators\ValidatorInterface;

/**
 * Class RegistrationFactory
 * @package Perevorot\Users\Classes
 */
class PlanValidationFactory
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
                return new PlanStepOneValidator($data);
                break;
            case 2:
                return new PlanStepTwoValidator($data);
                break;

            default:
                throw new \Exception('Current step don\'t found');
        }
    }
}
