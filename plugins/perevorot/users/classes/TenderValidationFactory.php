<?php

namespace Perevorot\Users\Classes;

use Perevorot\Users\Classes\Validators\TenderStepOneAValidator;
use Perevorot\Users\Classes\Validators\TenderStepOneValidator;
use Perevorot\Users\Classes\Validators\TenderStepSixValidator;
use Perevorot\Users\Classes\Validators\TenderStepThreeValidator;
use Perevorot\Users\Classes\Validators\TenderStepTwoValidator;
use Perevorot\Users\Classes\Validators\TenderStepFourValidator;
use Perevorot\Users\Classes\Validators\TenderStepFiveValidator;
use Perevorot\Users\Classes\Validators\ValidatorInterface;

/**
 * Class RegistrationFactory
 * @package Perevorot\Users\Classes
 */
class TenderValidationFactory
{
    /**
     * @param array $data
     * @param $step
     * @return ValidatorInterface
     * @throws \Exception
     */
    public static function make(array $data, $step, $_form_validation_field = false)
    {
        switch ($step) {
            case 1:
                return new TenderStepOneValidator($data, $_form_validation_field);
                break;

            case 8:
                return new TenderStepOneAValidator($data, $_form_validation_field);
                break;

            case 2:
                return new TenderStepTwoValidator($data, $_form_validation_field);
                break;

            case 3:
                return new TenderStepThreeValidator($data, $_form_validation_field);
                break;

            case 4:
                return new TenderStepFourValidator($data, $_form_validation_field);
                break;

            case 5:
                return new TenderStepFiveValidator($data, $_form_validation_field);
                break;

            case 6:
                return new TenderStepSixValidator($data, $_form_validation_field);
                break;

            default:
                throw new \Exception('Current step don\'t found');
        }
    }
}
