<?php

namespace Perevorot\Users\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\Classifier;
use Perevorot\Rialtotender\Models\Currency;
use Perevorot\Rialtotender\Models\FormMessage;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Users\Components\RegistrationForm;
use Perevorot\Users\Components\PlanCreate;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\MessagePlan;
use Perevorot\Users\Models\User;
use Perevorot\Page\Models\Page;
use App;
use Psy\Exception\ErrorException;

/**
 * Class RegistrationRenderFormTrait
 * @package Perevorot\Users\Traits
 */
trait PlanRenderFormTrait
{
    /**
     * @var PlanMessage $messages
     */
    private $messages;

    /**
     * @param $step
     * @return bool|void
     * @throws InvalidUserFromSession
     */
    private function renderStepFactory($step)
    {
        $this->plan = $this->getPlan();

        if($step == 1 && !$this->plan) {
            $this->plan = new Plan();
        }

        $this->messages = MessagePlan::instance();

        if (!($this->plan instanceof Plan)) {
            Session::remove('plan.session');
            Session::remove('plan.id');
            Session::remove('plan.update');

            return redirect()->to($this->siteLocale.'plan/search#'.$this->is_gov.'plans');
            //throw new \Exception();
        }

        switch ($step) {
            case 1:
                return $this->renderStepOne();

            case 2:
                return $this->renderStepTwo();

            default:
                return false;
        }
    }

    /**
     * @return string
     */
    private function renderStepOne()
    {
        \IntegerLog::info('plan.add.step1');

        Session::put('plan.session', 1);        
        
        $years = [Carbon::now()->year, Carbon::now()->addYear()->year];

        return [
            'template' => PlanCreate::STEP_ONE_TEMPLATE,
            'params'   => [
                'text'  => $this->getText(1),
                'header' => $this->getHeader(1),
                'plan'  => $this->plan->getJson(),
                'plan_id' => $this->plan->id,
                'procurement_types' => Procurement::getData('_type'),
                'procurement_method_types' => Procurement::getData('_method_type'),
                'currencies' => Currency::all(),
                'years' => $years,
                'CurrentLocale' => $this->getCurrentLocaleWithoutSlash(),
                'show_p_types' => $this->setting->checkAccess('procurementMethod'),
                'show_p_method_types' => $this->setting->checkAccess('procurementMethodType'),
                'show_p_method_types_default' => $this->setting->checkAccess('procurementMethodTypeDefault'),
            ]
        ];
    }

    /**
     * @return string
     */
    private function renderStepTwo()
    {
        \IntegerLog::info('plan.add.step2');
        
        $json = $this->plan->getJson();

        if(isset($json->items)) {
            $items = (array)$json->items;
        }
        else{
            $items = [];
        }

        $citems = count($items);

        return [
            'template' => PlanCreate::STEP_TWO_TEMPLATE,
            'params'   => [
                'user' => Auth::getUser(),
                'text'  => $this->getText(2),
                'header' => $this->getHeader(2),
                'plan' => $json,
                'items'  => $items,
                'classifier_name' => Classifier::getCpvByCode($json->classification->id),
                'plan_id' => $this->plan->id,
                'edit' => Session::get('plan.update'),
                'item_index' => $citems ? $citems : 1,
                'measurers' => Classifier::getMeasurers()
            ]
        ];
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getText($step)
    {
        return ($this->messages ? $this->messages->{'step' . $step} : '');
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getHeader($step)
    {
        return ($this->messages ? $this->messages->{'header' . $step} : '');
    }
}
