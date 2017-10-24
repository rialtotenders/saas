<?php

namespace Perevorot\Rialtotender\Classes;

use Perevorot\Form\Traits\ContractPageValidator;
use Perevorot\Form\Traits\ContractsValidator;
use Perevorot\Form\Traits\CustomValidatorMessages;
use Perevorot\Uploader\Components\FileUploader;
use Perevorot\Users\Classes\Validators\ContractStepOneValidator;
use Perevorot\Users\Classes\Validators\ContractStepThreeValidator;
use Perevorot\Users\Classes\Validators\PlanStepOneValidator;
use Perevorot\Users\Classes\Validators\PlanStepTwoValidator;
use Perevorot\Users\Classes\Validators\StepOneValidator;
use Perevorot\Users\Classes\Validators\StepThreeValidator;
use Perevorot\Users\Classes\Validators\StepTwoValidator;
use Perevorot\Users\Classes\Validators\TenderStepFiveValidator;
use Perevorot\Users\Classes\Validators\TenderStepFourValidator;
use Perevorot\Users\Classes\Validators\TenderStepOneAValidator;
use Perevorot\Users\Classes\Validators\TenderStepOneValidator;
use Perevorot\Users\Classes\Validators\TenderStepSixValidator;
use Perevorot\Users\Classes\Validators\TenderStepThreeValidator;
use Perevorot\Users\Models\User;
use RainLab\Translate\Models\Message;
use Perevorot\Users\Traits\TenderCancellingValidator;

class ValidationMessages
{
    public static function generateCustomMessages(&$instance, $type, $explode = false)
    {
        if(is_object($instance)) {

            foreach ($instance->customMessages as $code => $message) {

                $_code = null;

                if ($explode) {
                    $_code = self::explodeCode($code);
                }

                $instance->customMessages[$code] = Message::get($type . '.' . ($_code ? $_code : $code));
            }
        }
        elseif(!empty($instance)) {
            foreach ($instance as $code => $message) {

                $_code = null;

                if ($explode) {
                    $_code = self::explodeCode($code);
                }

                $instance[$code] = Message::get($type . '.' . ($_code ? $_code : $code));
            }

            return $instance;
        }

        return [];
    }

    private static function explodeCode($code) {

        $_code = explode(".", $code);

        foreach($_code AS $k => $v) {
            if(is_numeric($v)) {
                unset($_code[$k]);
            }
        }

        $_code = implode(".", $_code);


        return $_code;
    }

    public static function getMessageCodes()
    {
        $objectCodes = [
            PlanStepOneValidator::$messageCodes,
            TenderStepOneValidator::$messageCodes,
            TenderStepThreeValidator::$messageCodes,
            TenderStepFourValidator::$messageCodes,
            TenderStepFiveValidator::$messageCodes,
            StepThreeValidator::$messageCodes,
            StepTwoValidator::$messageCodes,
            StepOneValidator::$messageCodes,
            User::$messageCodes,
            CustomValidatorMessages::$messageCodes,
            TenderStepSixValidator::$messageCodes,
            PlanStepTwoValidator::$messageCodes,
            FileUploader::$messagesCode,
            TenderCancellingValidator::$messageCodes,
            TenderStepOneAValidator::$messageCodes,
            ContractsValidator::$messageCodes,
            ContractStepOneValidator::$messageCodes,
            ContractStepThreeValidator::$messageCodes,
            ContractPageValidator::$messageCodes,
        ];

        $_codes = [];

        foreach ($objectCodes as $codes) {
            $_codes = array_merge($_codes, $codes);
        }

        return $_codes;
    }
}
