<?php

namespace Perevorot\Users\Classes\Validators;
use Perevorot\Rialtotender\Models\Setting;

/**
 * Class StepOneValidator
 * @package Perevorot\Users\Classes\Validators
 */
class ProfileValidation extends ValidatorInterface
{
    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $customMessages = [];

    public function __construct($data)
    {
        foreach(['One', 'Two', 'Three'] as $step)
        {
            $className='Perevorot\Users\Classes\Validators\Step'.$step.'Validator';
            $instance=new $className($data);
            
            $this->rules=array_merge($this->rules, $instance->rules);
            $this->customMessages=array_merge($this->customMessages, $instance->customMessages);
        }

        $this->rules['password'] = 'between:4,255|confirmed';
        $this->rules['email'] = 'required|email';
        $this->rules['iban'] = 'alpha_num|min:24|max:24';

        $setting = Setting::instance();

        if($setting->checkAccess('user_edit_group')) {
            unset($this->rules['roles']);
        }

        $this->data=$data;
    }
}
