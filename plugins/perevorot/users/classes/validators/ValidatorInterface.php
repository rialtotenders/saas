<?php

namespace Perevorot\Users\Classes\Validators;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tender;

/**
 * Class ValidatorInterface
 * @package Perevorot\Users\Classes\Validators
 */
abstract class ValidatorInterface
{
    private $validate_by_field;

    private $setting;
    /**
     * @var
     */
    protected $data;

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $customMessages = [];

    /**
     * ValidatorInterface constructor.
     * @param $data
     */
    public function __construct($data, $_validate_by_field = false)
    {
        $this->validate_by_field = $_validate_by_field;
        $this->data = $data;
        $this->setting = Setting::instance();

        if($this instanceof StepOneValidator) {
            if(!$this->setting->checkAccess('requiredUserPostalCode')) {
                unset($this->rules['company_index']);
            } elseif($this->setting->get_value('userPostalCodeLength')) {
                $this->rules['company_index'] .= '|digits:'.intval(trim($this->setting->get_value('userPostalCodeLength')));

                if($this->setting->get_value('userPostalCodePrefix')) {
                    $this->rules['company_index'] = 'required|with_prefix:' . trim($this->setting->get_value('userPostalCodePrefix').','.$this->setting->get_value('userPostalCodeLength'));
                }
            }
        }
        elseif($this instanceof ContractStepOneValidator) {
            /*
            if(isset($this->data['change']['rationaleTypes']) || empty($this->data['change']['rationaleTypes'])) {
                return null;
            }

            $_edit_period = ['durationExtension', 'fiscalYearExtension'];
            $_edit_amount = ['itemPriceVariation', 'volumeCuts', 'thirdParty', 'taxRate'];
            $_edit = false;

            foreach($_edit_amount AS $v) {
                if (in_array($v, $this->data['change']['rationaleTypes'])) {
                    $_edit = true;
                    break;
                }
            }

            if(!$_edit) {
                unset($this->rules['value.amount']);
            }

            $_edit = false;

            foreach($_edit_period AS $v) {
                if (in_array($v, $this->data['change']['rationaleTypes'])) {
                    $_edit = true;
                    break;
                }
            }

            if(!$_edit) {
                unset($this->rules['period.startDate']);
                unset($this->rules['period.endDate']);
            }
            */

        }
        elseif($this instanceof PlanStepOneValidator) {

            if($this->setting->checkAccess('procurementMethod')) {
                $this->rules['tender.procurementMethod'] = 'required';
            }

            if($this->setting->checkAccess('procurementMethodType')) {
                $this->rules['tender.procurementMethodType'] = 'required';
            }

            if(Session::get('plan.update')) {
                $tender = Plan::find(Session::get('plan.id'));

                if (!$tender) {
                    return null;
                }

                $tender = $tender->getJson();

                if(isset($tender->items)) {
                    $_item_key = 'items.0';
                    $this->rules['month'] .= "|greater_than_field:plan,".$_item_key . '.deliveryDate.startDate';
                }
            }
        }
        elseif($this instanceof TenderStepOneValidator) {

            /*
            if($this->setting->checkAccess('procurementMethod')) {
                $this->rules['procurementMethod'] = 'required';
            }
            */

            if($this->setting->checkAccess('procurementMethodType')) {
                $this->rules['procurementMethodType'] = 'required';
            }

            /*
            if(isset($this->rules['procurementMethodType']) && $this->data['procurementMethodType'] == 'aboveThresholdTS') {
                $this->rules['procurementMethodType'] = 'custom_required_with:procurementMethod,open';
            }
            */

            if(!$this->setting->checkAccess('requiredDescription')) {
                unset($this->rules['description']);
            }
        }
        elseif($this instanceof TenderStepThreeValidator) {

            $tender = Tender::find(Session::get('tender.id'));

            if(!$tender) {
                return null;
            }

            $tender = $tender->getJson();

            if($this->setting->checkAccess('withEmptyPrice') && @$this->data['is_empty_price']) {
                unset($this->rules['value.amount']);
                $this->rules['minimalStep.amount'] = 'required|numeric|between:1,999999999';

                if (!$this->setting->checkAccess('guarantee')) {
                    unset($this->rules['guarantee.amount']);
                }

            } else {
                if (!$tender->lot) {
                    if ($this->setting->get_value('auction_step_from') || $this->setting->get_value('auction_step_to')) {
                        if (isset($this->data['value']['amount']) && $this->data['value']['amount'] > 0) {
                            $this->data['minimalStep']['amount'] = ($this->data['minimalStep']['amount'] * 100) / $this->data['value']['amount'];
                        }

                        $this->rules['minimalStep.amount'] = 'required|numeric|between:' . $this->setting->get_value('auction_step_from') . ',' . $this->setting->get_value('auction_step_to');
                    } else {
                        $this->rules['minimalStep.amount'] = 'required|numeric';
                    }

                    if (!$this->setting->checkAccess('guarantee')) {
                        unset($this->rules['guarantee.amount']);
                    }

                } else {
                    unset($this->rules['minimalStep.amount']);
                    unset($this->rules['value.amount']);
                    unset($this->rules['guarantee.amount']);
                }
            }
        }
        elseif($this instanceof TenderStepOneAValidator) {
            foreach ($this->data as $key => $items) {
                if ($key == 'features') {
                    foreach ($items as $findex => $feature) {
                        if($feature['title'] || !empty($feature['enum'])) {

                            $_isset_enum = false;
                            $_feature_key = 'features.' . $findex;

                            $this->rules[$_feature_key . '.title'] = 'required|need_zero_enum';
                            $this->customMessages[$_feature_key . '.title.required'] = '';
                            $this->customMessages[$_feature_key .'.title.need_zero_enum'] = '';

                            foreach ($feature['enum'] as $eindex => $enum) {
                                if($feature['title'] || $enum['title'] || $enum['value']) {

                                    $_isset_enum = true;
                                    $_enum_key = $_feature_key . '.enum.' . $eindex;

                                    $this->rules[$_enum_key . '.title'] = 'required_with:' . $_feature_key . '.title|required_with:' . $_enum_key . '.value';
                                    $this->customMessages[$_enum_key . '.title.required_with'] = '';

                                    $this->rules[$_enum_key . '.value'] = 'required_with:' . $_feature_key . '.title|required_with:' . $_enum_key . '.title|numeric|check_tender_enum_value';
                                    $this->customMessages[$_enum_key . '.value.required_with'] = '';
                                    $this->customMessages[$_enum_key . '.value.numeric'] = '';
                                    $this->customMessages[$_enum_key . '.value.check_tender_enum_value'] = '';
                                }
                            }

                            if(!$_isset_enum && !$feature['title'] ) {
                                $this->rules = [];
                            }
                        }
                    }
                }
            }
        }
        elseif($this instanceof TenderStepFiveValidator) {

            $tender = Tender::find(Session::get('tender.id'));

            if(!$tender) {
                return null;
            }

            $tender = $tender->getJson();

            if($tender->lot) {
                foreach ($this->data as $key => $lots) {
                    if ($key == 'lots') {
                        foreach ($lots as $lot_index => $lot) {

                            $_lot_key = 'lots.' . $lot_index;

                            if ($this->setting->get_value('auction_step_from') || $this->setting->get_value('auction_step_to')) {

                                if(isset($lot['value']['amount']) && $lot['value']['amount'] > 0 && $lot['minimalStep']['amount'] > 0) {
                                    $this->data[$key][$lot_index]['minimalStep']['amount'] = ($lot['minimalStep']['amount'] * 100) / $lot['value']['amount'];
                                }

                                $this->rules[$_lot_key . '.minimalStep.amount'] = 'required|numeric|between:' . $this->setting->get_value('auction_step_from') . ',' . $this->setting->get_value('auction_step_to');
                                $this->customMessages[$_lot_key . '.minimalStep.amount.between'] = '';

                            } else {
                                $this->rules[$_lot_key . '.minimalStep.amount'] = 'required|numeric';
                            }

                            if (isset($lot['features'])) {
                                foreach ($lot['features'] as $findex => $feature) {
                                    if ($feature['title'] || !empty($feature['enum'])) {

                                        $_isset_enum = false;
                                        $_feature_key = $_lot_key . '.features.' . $findex;

                                        $this->rules[$_feature_key . '.title'] = 'required|need_zero_enum';
                                        $this->customMessages[$_feature_key . '.title.required'] = '';
                                        $this->customMessages[$_feature_key .'.title.need_zero_enum'] = '';

                                        foreach ($feature['enum'] as $eindex => $enum) {
                                            if($feature['title'] || $enum['title'] || $enum['value']) {

                                                $_isset_enum = true;
                                                $_enum_key = $_feature_key . '.enum.' . $eindex;

                                                $this->rules[$_enum_key . '.title'] = $rule = 'required';
                                                $this->customMessages[$_enum_key . '.title.' . $rule] = '';

                                                $this->rules[$_enum_key . '.value'] = $rule = 'required|numeric|check_lot_enum_value:'.$_lot_key;
                                                $this->customMessages[$_enum_key . '.value.required'] = '';
                                                $this->customMessages[$_enum_key . '.value.numeric'] = '';
                                                $this->customMessages[$_enum_key . '.value.check_lot_enum_value'] = '';
                                            }
                                        }

                                        if(!$_isset_enum && !$feature['title'] ) {
                                            unset($this->rules[$_feature_key . '.title']);
                                        }
                                    }
                                }
                            }

                            $this->customMessages[$_lot_key . '.minimalStep.amount.required'] = '';
                            $this->customMessages[$_lot_key . '.minimalStep.amount.numeric'] = '';

                            $this->rules[$_lot_key . '.value.amount'] = 'required|numeric|between:0,1000000000';
                            $this->customMessages[$_lot_key . '.value.amount.required'] = '';
                            $this->customMessages[$_lot_key . '.value.amount.numeric'] = '';
                            $this->customMessages[$_lot_key . '.value.amount.between'] = '';

                            if($this->setting->checkAccess('withEmptyPrice') && @$lot['is_empty_price']) {
                                unset($this->rules[$_lot_key . '.value.amount']);
                                $this->rules[$_lot_key . '.minimalStep.amount'] = 'required|numeric|between:1,999999999';
                            }

                            $this->rules[$_lot_key . '.guarantee.amount'] = 'required|numeric';
                            $this->customMessages[$_lot_key . '.guarantee.amount.required'] = '';
                            $this->customMessages[$_lot_key . '.guarantee.amount.numeric'] = '';

                            if(!$this->setting->checkAccess('guarantee')) {
                                unset($this->rules[$_lot_key . '.guarantee.amount']);
                            }

                            $this->rules[$_lot_key . '.title'] = $rule = 'required';
                            $this->customMessages[$_lot_key . '.title.' . $rule] = '';
                            $this->rules[$_lot_key . '.description'] = $rule = 'required';
                            $this->customMessages[$_lot_key . '.description.' . $rule] = '';

                            foreach ($lot as $lot_key => $items) {
                                if ($lot_key == 'items') {
                                    foreach ($items as $item_index => $item) {

                                        $_item_key = $_lot_key . '.items.' . $item_index;

                                        $this->rules[$_item_key . '.deliveryAddress.locality'] = $rule = 'required';
                                        $this->customMessages[$_item_key . '.deliveryAddress.locality.' . $rule] = '';

                                        $this->rules[$_item_key . '.deliveryAddress.streetAddress'] = $rule = 'required';
                                        $this->customMessages[$_item_key . '.deliveryAddress.streetAddress.' . $rule] = '';

                                        $this->rules[$_item_key . '.deliveryAddress.region'] = $rule = 'required';
                                        $this->customMessages[$_item_key . '.deliveryAddress.region.' . $rule] = '';

                                        $this->rules[$_item_key . '.deliveryAddress.postalCode'] = 'required';
                                        $this->customMessages[$_item_key . '.deliveryAddress.postalCode.required'] = '';
                                        $this->customMessages[$_item_key . '.deliveryAddress.postalCode.digits'] = '';
                                        $this->customMessages[$_item_key . '.deliveryAddress.postalCode.with_prefix'] = '';

                                        if(!$this->setting->checkAccess('requiredPostalCode')) {
                                            unset($this->rules[$_item_key . '.deliveryAddress.postalCode']);
                                        } elseif($this->setting->get_value('postalCodeLength')) {
                                            $this->rules[$_item_key . '.deliveryAddress.postalCode'] .= '|digits:'.intval(trim($this->setting->get_value('postalCodeLength')));

                                            if($this->setting->get_value('postalCodePrefix')) {
                                                $this->rules[$_item_key . '.deliveryAddress.postalCode'] = 'required|with_prefix:' . trim($this->setting->get_value('postalCodePrefix').','.$this->setting->get_value('postalCodeLength'));
                                            }
                                        }

                                        $this->rules[$_item_key . '.deliveryDate.endDate'] = "required|greater_than_field:tender,tenderPeriod.endDate|greater_than_field:$_item_key.deliveryDate.startDate";
                                        $this->customMessages[$_item_key . '.deliveryDate.endDate.required'] = '';
                                        $this->customMessages[$_item_key . '.deliveryDate.endDate.greater_than_field'] = '';

                                        $this->rules[$_item_key . '.deliveryDate.startDate'] = "required|greater_than_field:tender,tenderPeriod.endDate";
                                        $this->customMessages[$_item_key . '.deliveryDate.startDate.required'] = '';
                                        $this->customMessages[$_item_key . '.deliveryDate.startDate.less_than_field'] = '';
                                        $this->customMessages[$_item_key . '.deliveryDate.startDate.greater_than_field'] = '';

                                        $this->rules[$_item_key . '.unit.code'] = $rule = 'required';
                                        $this->customMessages[$_item_key . '.unit.code.' . $rule] = '';

                                        $this->rules[$_item_key . '.quantity'] = 'required|numeric';
                                        $this->customMessages[$_item_key . '.quantity.required'] = '';
                                        $this->customMessages[$_item_key . '.quantity.numeric'] = '';

                                        $this->rules[$_item_key . '.classification.id'] = $rule = 'required';
                                        $this->customMessages[$_item_key . '.classification.id.' . $rule] = '';

                                        $this->rules[$_item_key . '.description'] = $rule = 'required';
                                        $this->customMessages[$_item_key . '.description.' . $rule] = '';
                                    }
                                }
                            }
                        }
                    }
                }

            } else {
                foreach ($this->data as $key => $items) {
                    if ($key == 'items') {
                        foreach ($items as $index => $item) {

                            $_item_key = 'items.' . $index;

                            $this->rules[$_item_key . '.deliveryAddress.locality'] = $rule = 'required';
                            $this->customMessages[$_item_key . '.deliveryAddress.locality.' . $rule] = '';

                            $this->rules[$_item_key . '.deliveryAddress.streetAddress'] = $rule = 'required';
                            $this->customMessages[$_item_key . '.deliveryAddress.streetAddress.' . $rule] = '';

                            $this->rules[$_item_key . '.deliveryAddress.region'] = $rule = 'required';
                            $this->customMessages[$_item_key . '.deliveryAddress.region.' . $rule] = '';

                            $this->rules[$_item_key . '.deliveryAddress.postalCode'] = 'required|digits:5';
                            $this->customMessages[$_item_key . '.deliveryAddress.postalCode.required'] = '';
                            $this->customMessages[$_item_key . '.deliveryAddress.postalCode.digits'] = '';
                            $this->customMessages[$_item_key . '.deliveryAddress.postalCode.with_prefix'] = '';

                            if(!$this->setting->checkAccess('requiredPostalCode')) {
                                unset($this->rules[$_item_key . '.deliveryAddress.postalCode']);
                            } elseif($this->setting->get_value('postalCodeLength')) {
                                $this->rules[$_item_key . '.deliveryAddress.postalCode'] .= '|digits:'.intval(trim($this->setting->get_value('postalCodeLength')));

                                if($this->setting->get_value('postalCodePrefix')) {
                                    $this->rules[$_item_key . '.deliveryAddress.postalCode'] = 'required|with_prefix:' . trim($this->setting->get_value('postalCodePrefix').','.$this->setting->get_value('postalCodeLength'));
                                }
                            }

                            $this->rules[$_item_key . '.deliveryDate.endDate'] = "required|greater_than_field:tender,tenderPeriod.endDate|greater_than_field:items.$index.deliveryDate.startDate";
                            $this->customMessages[$_item_key . '.deliveryDate.endDate.required'] = '';
                            $this->customMessages[$_item_key . '.deliveryDate.endDate.greater_than_field'] = '';

                            $this->rules[$_item_key . '.deliveryDate.startDate'] = "required|greater_than_field:tender,tenderPeriod.endDate";
                            $this->customMessages[$_item_key . '.deliveryDate.startDate.required'] = '';
                            $this->customMessages[$_item_key . '.deliveryDate.startDate.less_than_field'] = '';
                            $this->customMessages[$_item_key . '.deliveryDate.startDate.greater_than_field'] = '';

                            $this->rules[$_item_key . '.unit.code'] = $rule = 'required';
                            $this->customMessages[$_item_key . '.unit.code.' . $rule] = '';

                            $this->rules[$_item_key . '.quantity'] = 'required|numeric';
                            $this->customMessages[$_item_key . '.quantity.required'] = '';
                            $this->customMessages[$_item_key . '.quantity.numeric'] = '';

                            $this->rules[$_item_key . '.classification.id'] = $rule = 'required';
                            $this->customMessages[$_item_key . '.classification.id.' . $rule] = '';

                            $this->rules[$_item_key . '.description'] = $rule = 'required';
                            $this->customMessages[$_item_key . '.description.' . $rule] = '';
                        }
                    }
                }
            }
        }
        else if($this instanceof PlanStepTwoValidator) {
            foreach ($this->data as $key => $items) {
                if ($key == 'items') {
                    foreach ($items as $index => $item) {

                        $_item_key = 'items.' . $index;

                        $this->rules[$_item_key . '.deliveryDate.endDate'] = "required|greater_than_field:plan,tender.tenderPeriod.startDate|greater_than_field:items.$index.deliveryDate.startDate";
                        $this->customMessages[$_item_key . '.deliveryDate.endDate.required'] = '';
                        $this->customMessages[$_item_key . '.deliveryDate.endDate.greater_than_field'] = '';

                        $this->rules[$_item_key . '.deliveryDate.startDate'] = "required|greater_than_field:plan,tender.tenderPeriod.startDate";
                        $this->customMessages[$_item_key . '.deliveryDate.startDate.required'] = '';
                        $this->customMessages[$_item_key . '.deliveryDate.startDate.less_than_field'] = '';
                        $this->customMessages[$_item_key . '.deliveryDate.startDate.greater_than_field'] = '';

                        $this->rules[$_item_key . '.unit.code'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.unit.code.' . $rule] = '';

                        $this->rules[$_item_key . '.quantity'] = 'required|numeric';
                        $this->customMessages[$_item_key . '.quantity.required'] = '';
                        $this->customMessages[$_item_key . '.quantity.numeric'] = '';

                        $this->rules[$_item_key . '.classification.id'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.classification.id.' . $rule] = '';

                        $this->rules[$_item_key . '.description'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.description.' . $rule] = '';
                    }
                }
            }
        } else if($this instanceof ContractStepThreeValidator) {
            foreach ($this->data as $key => $items) {
                if ($key == 'items') {
                    foreach ($items as $index => $item) {

                        $_item_key = 'items.' . $index;

                        $this->rules[$_item_key . '.unit.code'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.unit.code.' . $rule] = '';

                        $this->rules[$_item_key . '.quantity'] = 'required|numeric';
                        $this->customMessages[$_item_key . '.quantity.required'] = '';
                        $this->customMessages[$_item_key . '.quantity.numeric'] = '';

                        $this->rules[$_item_key . '.classification.id'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.classification.id.' . $rule] = '';
                        $this->rules[$_item_key . '.classification.scheme'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.classification.scheme.' . $rule] = '';
                        $this->rules[$_item_key . '.classification.description'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.classification.description.' . $rule] = '';

                        $this->rules[$_item_key . '.description'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.description.' . $rule] = '';

                        $this->rules[$_item_key . '.deliveryAddress.locality'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.deliveryAddress.locality.' . $rule] = '';

                        $this->rules[$_item_key . '.deliveryAddress.streetAddress'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.deliveryAddress.streetAddress.' . $rule] = '';

                        $this->rules[$_item_key . '.deliveryAddress.region'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.deliveryAddress.region.' . $rule] = '';

                        $this->rules[$_item_key . '.deliveryAddress.postalCode'] = $rule = 'required';
                        $this->customMessages[$_item_key . '.deliveryAddress.postalCode.' . $rule] = '';
                    }
                }
            }
        } else if($this instanceof TenderStepFourValidator) {

            $tender = Tender::find(Session::get('tender.id'));

            if(!$tender) {
                return null;
            }

            $_tender = $tender->getJson();

            if(isset($_tender->procurementMethodType) && $_tender->procurementMethodType == 'aboveThresholdTS') {
                unset($this->rules['enquiryPeriod.endDate']);
                unset($this->rules['tenderPeriod.startDate']);

                $min_days = $this->setting->get_value('createTwoStageTenderPeriod');

                if($min_days == null) {
                    $min_days = 5;
                }

                if($tender->is_complete) {
                    $eq_end = Carbon::createFromFormat('d.m.Y H:i', $_tender->tenderPeriod->endDate)->diffInDays();

                    if($eq_end < 3) {

                        $min_days = $this->setting->get_value('editTwoStageTenderPeriod');

                        if($min_days == null) {
                            $min_days = 3;
                        }
                    }
                }

                $this->rules['tenderPeriod.endDate'] = 'required|date|greater_than_now:'.$min_days.',days';
            }
        }

        if(is_array($this->validate_by_field)) {
            foreach($this->rules AS $field => $rule) {
                if(!in_array($field, $this->validate_by_field)) {
                    unset($this->rules[$field]);
                }
            }
        }

        $multi = false;

        if($this instanceof PlanStepOneValidator || $this instanceof PlanStepTwoValidator) {
            $object = 'plan';
            $multi = $this instanceof PlanStepTwoValidator;
        } elseif (
            $this instanceof TenderStepOneValidator || $this instanceof TenderStepThreeValidator ||
            $this instanceof TenderStepFourValidator || $this instanceof TenderStepFiveValidator ||
            $this instanceof TenderStepSixValidator || $this instanceof TenderStepOneAValidator
        ) {
            $object = 'tender';
            $multi = ($this instanceof TenderStepFiveValidator || $this instanceof TenderStepOneAValidator);
        } elseif(
            $this instanceof StepOneValidator || $this instanceof StepTwoValidator ||
            $this instanceof StepThreeValidator || $this instanceof ProfileValidation
        ) {
            $object = 'registration';
        } elseif(
            $this instanceof ContractStepOneValidator || $this instanceof ContractStepThreeValidator
        ) {
            $object = 'contract';
            $multi = $this instanceof ContractStepThreeValidator;
        }

        if(isset($object)) {
            ValidationMessages::generateCustomMessages($this, $object, $multi);
        }
    }

    /**
     * @throws ValidationException
     */
    public function validate()
    {
        $validator = Validator::make($this->data, $this->rules, $this->customMessages);

        if ($validator->fails()) {
            if($this->validate_by_field) {
                return $validator->messages();
            } else {
                throw new ValidationException($validator);
            }
        }

        return true;
    }
}
