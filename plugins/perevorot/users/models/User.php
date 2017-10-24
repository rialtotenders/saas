<?php

namespace Perevorot\Users\Models;

use Carbon\Carbon;
use Perevorot\Users\Models\ExternalAuth;
use Illuminate\Support\Facades\DB;
use Model;
use Perevorot\Rialtotender\Models\Payment;
use Perevorot\Rialtotender\Models\Tariff;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Traits\UserModelMutatorsTrait;
use Perevorot\Users\Traits\UserModelTrait;
use RainLab\User\Models\User as BaseUser;
use RainLab\User\Models\Settings as UserSettings;

/**
 * User Model
 */
class User extends BaseUser
{
    use CurrentLocale, UserModelTrait, UserModelMutatorsTrait;

    /**
     * @var array
     */
    public $rules = [
        'email'    => 'required|email',
        'username' => 'required|unique:users',//'required|digits:8|unique:users',
        'password' => 'required:create|between:4,255',
    ];

    /**
     * @var array
     */
    public $customMessages = [
        'email.required'                => '',
        'email.email'                   => '',
        'email.unique'                  => '',
        'username.required'             => '',
        'username.digits'               => '',
        'username.unique'               => '',
        'password.required'             => '',
        'password.between'              => '',
    ];

    public static $messageCodes = [
        'registration.email.required',
        'registration.email.email',
        'registration.email.unique' ,
        'registration.username.required',
        'registration.username.digits'  ,
        'registration.username.unique'  ,
        'registration.password.required',
        'registration.password.between' ,
    ];

    public $hasMany = [
        'payments' =>
            'Perevorot\Rialtotender\Models\Payment',
        'read_payments' =>
            'Perevorot\Rialtotender\Models\Payment',
        'applications' => [
            'Perevorot\Rialtotender\Models\Application',
            'key' => 'user_id',
            'otherKey' => 'id'
        ],
    ];

    public function money()
    {
        return self::select(DB::raw('SUM(perevorot_rialtotender_payments.sum) AS sum'))
            ->join('perevorot_rialtotender_payments', 'perevorot_rialtotender_payments.user_id', '=', 'users.id')
            ->where('users.id', $this->id)
            ->value('sum');
    }

    /**
     * @return array
     */
    public function groupCodes()
    {
        $codes = [];

        foreach ($this->groups as $group) {
            $codes[] = $group->code;
        }

        return $codes;
    }

    /**
     * Before validation event
     * @return void
     */
    public function beforeValidate()
    {
        /*
         * Guests are special
         */
        if ($this->is_guest && !$this->password) {
            $this->generatePassword();
        }
    }

    public function checkGroup($code)
    {
        foreach($this->groups as $group)
        {
            if($group->code == $code)
            {
                return true;
            }
        }

        return false;
    }

    public function pay($tender, $status, $bid = false)
    {
        if($tender->value->amount >= 999999999 && $bid) {
            $price = $bid->price;
        } else {
            $price = $tender->value->amount;
        }

        $issetTariff = false;
        $sum = 0;

        if($status != Payment::PAYMENT_TYPE_CANCELED_BID) {
            $tariff = Tariff::getTariff(['is_gov' => stripos($tender->tenderID, 'R-') === FALSE, 'price' => $price, 'currency' => $tender->value->currency]);
            $issetTariff = $this->checkMoney($tariff);
            $currency = $tariff->currency_id;
            $sum = $status == Payment::PAYMENT_TYPE_CREATED_BID ? -$tariff->sum : $tariff->sum;
        }

        if($status == Payment::PAYMENT_TYPE_CANCELED_BID) {
            if($p = Payment::where('application_id', $bid->id)->where('user_id', $this->id)->first()) {
                $sum = abs($p->sum);
                $currency = $p->currency_id;
            }
        }

        if($sum !== 0 && ($status == Payment::PAYMENT_TYPE_CANCELED_BID || $issetTariff)) {
            $payment = new Payment();
            $payment->user_id = $this->id;
            $payment->sum = $sum;
            $payment->currency_id = $currency;
            $payment->date = Carbon::now();
            $payment->type = $status;
            $payment->number = $tender->tenderID;
            $payment->application_id = $bid ? $bid->id : null;
            $payment->save();

            return true;
        }

        return false;
    }

    public $settings;

    public function getProfileUrl()
    {
        if(!$this->settings)
            $this->settings=ExternalAuth::instance();

        $url=$this->getCurrentLocale().'profile';

        if($this->settings->is_enabled && $this->settings->urlProfile)
            $url=$this->settings->urlProfile;
        
        return $url;
    }

    public function getLogoutUrl()
    {
        if(!$this->settings)
            $this->settings=ExternalAuth::instance();

        $url=false;

        if($this->settings->is_enabled && $this->settings->urlLogout)
            $url=$this->settings->urlLogout;

        return $url;
    }
    
    public function checkMoney($tariff = null)
    {
        if($tariff) {

            $money = $this->money();

            if ($money >= $tariff->sum || ($money < $tariff->sum && $this->is_overdraft)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sends the confirmation email to a user, after activating.
     * @param  string $code
     * @return void
     */
    /*
    public function attemptActivation($code)
    {
        $result = parent::attemptActivation($code);
        if ($result === false) {
            return false;
        }

        $mailTemplate = UserSettings::get('welcome_template');
        $mailTemplate = str_replace("::", ('::'.$this->getLocaleForEmail()), $mailTemplate);

        if ($mailTemplate) {
            Mail::sendTo($this, $mailTemplate, $this->getNotificationVars());
        }

        Event::fire('rainlab.user.activate', [$this]);

        return true;
    }*/
}
