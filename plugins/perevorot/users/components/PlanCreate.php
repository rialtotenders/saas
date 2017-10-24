<?php

namespace Perevorot\Users\Components;

use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Classes\PlanValidationFactory;
use Perevorot\Users\Classes\Validators\PlanStepOneValidator;
use Perevorot\Users\Traits\PlanTrait;
use Perevorot\Users\Traits\PlanUtils;
use Perevorot\Users\Facades\Auth;
use System\Helpers\DateTime;

/**
 * Class RegistrationForm
 * @package Perevorot\Users\Components
 */
class PlanCreate extends ComponentBase
{
    use CurrentLocale, PlanTrait, PlanUtils;

    /**
     * Template for step 1
     */
    const STEP_ONE_TEMPLATE = '@plancreate/_step1.htm';

    /**
     * Template for step 2
     */
    const STEP_TWO_TEMPLATE = '@plancreate/_step2.htm';

    /**
     * @var
     */
    private $step;
    public $siteLocale;
    private $setting;
    public $user;
    public $is_gov;
    public $source;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'PlanCreate',
            'description' => 'Plan create by user'
        ];
    }

    /**
     * @return array
     */
    public function defineProperties()
    {
        return [
            'type' => [
                'label' => 'Тип'
            ]
        ];
    }

    public function init()
    {
        $this->siteLocale = $this->getCurrentLocale();
        $this->setting = Setting::instance();
        $this->user = Auth::getUser();
        $this->is_gov = $this->user->is_gov ? 'gov-' : null;
        $this->source = $this->param('source');
    }

    /**
     * @return array|RedirectResponse|mixed|string
     */
    public function onRun()
    {
        if($this->is_gov && $this->source != 'gov') {
            return redirect()->to($this->getCurrentLocale().'plan/create/gov');
        }elseif(!$this->is_gov && $this->source == 'gov') {
            return redirect()->to($this->getCurrentLocale().'plan/create');
        }

        if (!$this->user || !$this->user->checkGroup('customer'))
        {
            return redirect()->to($this->siteLocale);
        }

        if($planID = $this->param('planID')) {

            $params = ['test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1];

            if(is_numeric($planID)) {
                $params['id'] = $planID;
            } else {
                $params['plan_id'] = $planID;
            }

            if(($plan = Plan::getData($params)) instanceof Plan) {
                if(Session::get('plan.id') != $plan->id) {
                    Session::put('plan.id', $plan->id);
                    Session::put('plan.update', 1);
                    Session::put('plan.session', 1);
                }
            } else {
                return redirect()->to($this->siteLocale . 'plan/search#'.$this->is_gov.'plans');
            }
        }
        elseif(Session::get('plan.update'))
        {
            Session::remove('plan.session');
            Session::remove('plan.id');
            Session::remove('plan.update');
        }

        /** @var int step */
        $this->step = Session::get('plan.session', 1);

        $response = $this->plan();

        /** @var RedirectResponse|array $response */
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        $this->page['content'] = $response;
    }

    /**
     * @return mixed
     */
    public function onRender()
    {
        $this->addJs('assets/js/tender-validation.js');

        return $this->page['content'];
    }

    /**
     * Обработка шаблонов
     *
     * @return array|bool|string
     * @throws \Exception
     */
    public function onHandleForm()
    {
        $step = (int) post('step');
        $result = $this->processStepFactory($step, ($step != 1));

        if((boolean)post('save')) {
            Session::remove('plan.session');
            Session::remove('plan.id');
            Session::remove('plan.update');
            return redirect()->to($this->siteLocale . 'plan/search#'.$this->is_gov.'plans');
        }
        elseif ($result instanceof RedirectResponse) {
            return $result;
        }
        elseif ($result === true)
        {
            return redirect()->to($this->siteLocale . 'plan/search#'.$this->is_gov.'plans');
        }
        elseif ($result === false)
        {
            return [
                '#tender-access-error'=>$this->renderPartial('@messages/plan_access_error')
            ];
        }

        if (!array_key_exists('template', $result)) {
            throw new \Exception('Поле `template` должно быть объявлено');
        }

        $template = $result['template'];
        $params = (array_key_exists('params', $result)) ? $result['params'] : [];
        $params['CurrentLocale'] = $this->getCurrentLocaleWithoutSlash();
        $params['siteLocale'] = $this->siteLocale;

        return [
            '#plan-content' => $this->renderPartial($template, $params),
        ];
    }

    /**
     * @return mixed
     */
    public function onReturnBack()
    {
        $step = (int) post('step');

        return [
            '#plan-content' => $this->renderTemplateByStep($step),
        ];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function plan()
    {
        return $this->renderTemplateByStep($this->step);
    }
}
