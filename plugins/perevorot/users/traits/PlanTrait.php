<?php

namespace Perevorot\Users\Traits;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use October\Rain\Database\ModelException;
use October\Rain\Exception\ValidationException;
use Perevorot\Form\Classes\Api;
use Perevorot\Rialtotender\Exceptions\InvalidUserFromSession;
use Perevorot\Rialtotender\Models\FormMessage;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Users\Classes\PlanValidationFactory as ValidationFactory;
use Perevorot\Users\Classes\Validators\ValidatorInterface;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\Message;
use Perevorot\Users\Models\MessagePlan;
use Perevorot\Users\Models\User;
use Perevorot\Users\Models\UserGroup;

trait PlanTrait
{
    use PlanRenderFormTrait;

    private $plan;

    /**
     * @param $step
     * @param bool $user
     * @return bool|string
     * @throws InvalidUserFromSession
     */
    private function processStepFactory($step, $plan = true)
    {
        $this->plan = $this->getPlan();
        $this->messages = MessagePlan::instance();

        if (!$this->plan instanceof Plan)
        {
            if($step == 1) {
                $this->plan = new Plan();
            } else {
                Session::remove('plan.session');
                Session::remove('plan.id');
                Session::remove('plan.update');

                return redirect()->to($this->siteLocale.'plan/search#'.$this->is_gov.'plans');
            }
        }

        switch ($step) {
            case 1:
            case 3:
                return $this->processStepOne($step);
            case 2:
                return $this->processStepTwo($step);

            default:
                return false;
        }
    }

    /**
     * @return string
     * @throws ValidationException
     */
    private function processStepOne($step)
    {
        $data = $this->getPostData();

        /** @var ValidatorInterface $validator */
        $validator = ValidationFactory::make($data, 1);
        $validator->validate();

        $this->plan->processFields($data);
        $this->plan->save();

        $json = $this->plan->getJson();

        $this->plan->title = $json->budget->description;
        $this->plan->value = $json->budget->amount;
        $this->plan->currency = $json->budget->currency;
        $this->plan->save();

        Session::put('plan.id', $this->plan->id);

        if($step == 3)
        {
            return $this->processStepTwo(3);
        } else {
            return $this->renderStepTwo();
        }
    }

    /**
     * @return string
     */
    private function processStepTwo($step)
    {
        if($step == 2) {
            $data = $this->getPostData();

            /** @var ValidatorInterface $validator */
            $validator = ValidationFactory::make($data, 2);
            $validator->validate();

            $this->plan->processFields($data);
            $this->plan->save();
        }

        if((boolean)post('save')) {
            return true;
        }

        $api = new Api();

        if($api->createPlan($this->plan, Session::get('plan.update')))
        {
            $is_complete = $this->plan->is_complete;

            $this->plan->is_complete = 1;
            $this->plan->save();

            if(!$is_complete && $this->plan->is_complete) {
                Event::fire('perevorot.users.plan', [
                    'tender' => $this->plan,
                    'type' => 'created',
                ], true);
            } else {
                Event::fire('perevorot.users.plan', [
                    'tender' => $this->plan,
                    'type' => 'updated',
                ], true);
            }

            Session::remove('plan.session');
            Session::remove('plan.id');
            Session::remove('plan.update');

            return true;
        }

        return false;
    }
}
