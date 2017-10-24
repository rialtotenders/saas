<?php

namespace Perevorot\Users\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Traits\RegistrationFormTrait;
use Perevorot\Users\Traits\RegistrationFormUtils;
use RainLab\User\Components\Account;
use Perevorot\Users\Facades\Auth;
use RainLab\Translate\Classes\Translator;

/**
 * Class RegistrationForm
 * @package Perevorot\Users\Components
 */
class RegistrationForm extends Account
{
    use CurrentLocale, RegistrationFormTrait, RegistrationFormUtils;

    /**
     * Template for step 1
     */
    const STEP_ONE_TEMPLATE = 'registrationform/_step1.htm';

    /**
     * Template for step 2
     */
    const STEP_TWO_TEMPLATE = 'registrationform/_step2.htm';

    /**
     * Template for step 3
     */
    const STEP_THREE_TEMPLATE = 'registrationform/_step3.htm';

    /**
     * Template for step 4
     */
    const STEP_FOUR_TEMPLATE = 'registrationform/_step4.htm';

    const STEP_FIVE_TEMPLATE = 'registrationform/_step5.htm';

    /**
     * Registration type
     */
    const REGISTRATION_TYPE = 'registration';

    /**
     * Activation type
     */
    const ACTIVATION_TYPE = 'activation';

    /**
     * @var
     */
    private $step;
    public $siteLocale;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'RegistrationForm Component',
            'description' => 'No description provided yet...'
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
    }

    /**
     * @return array|RedirectResponse|mixed|string
     */
    public function onRun()
    {

        $response = '';

        /** @var int step */
        $this->step = Session::get('registration.session', 1);

        switch ($this->property('type')) {
            case RegistrationForm::ACTIVATION_TYPE:
                $response = $this->activation();
                break;

            case RegistrationForm::REGISTRATION_TYPE:
                $response = $this->registration();
                break;
        }

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
        $this->addJs('assets/js/payer-select.js');
        $this->addJs('assets/js/registration-validation.js');
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

        $result = $this->processStepFactory($step, ($step != 0));


        if ($result instanceof RedirectResponse) {
            return $result;
        }

        if (!array_key_exists('template', $result)) {
            throw new \Exception('Поле `template` должно быть объявлено');
        }

        $template = $result['template'];
        $params = (array_key_exists('params', $result)) ? $result['params'] : [];
        $setting = Setting::instance();
        $steps = $setting->get_value('show_offerta') ? 4 : 3;
        $params['steps'] = $steps;

        return [
            '#registration-content' => $this->renderPartial($template, $params),
        ];
    }

    /**
     * @return mixed
     */
    public function onReturnBack()
    {
        $step = (int) post('step');

        return [
            '#registration-content' => $this->renderTemplateByStep($step),
        ];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function registration()
    {
        return $this->renderTemplateByStep($this->step);
    }

    /**
     * @return mixed
     */
    private function activation()
    {
        \IntegerLog::info('user.activated');

        if ($activationCode = get('code')) {
            $this->onActivate($activationCode);
        }

        if (Auth::getUser()) {
            return Redirect::to($this->siteLocale.'welcome');
            //return $this->renderPartial('registrationform/_activated.htm');
        } else {
            return Redirect::to($this->siteLocale);
        }
    }
}
