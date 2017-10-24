<?php

namespace Perevorot\Users\Classes;

use Perevorot\Users\Classes\Validators\ContractStepOneValidator;
use Perevorot\Users\Classes\Validators\ContractStepThreeValidator;
use Perevorot\Users\Classes\Validators\ValidatorInterface;

class ContractValidationFactory
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
                return new ContractStepOneValidator($data);
                break;
            case 3:
                return new ContractStepThreeValidator($data);
                break;

            default:
                throw new \Exception('Current step don\'t found');
        }
    }
}
