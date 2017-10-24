<?php

namespace Perevorot\Users\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Users\Traits\ContractsTrait;
use Perevorot\Users\Traits\ContractsUtils;
use October\Rain\Support\Facades\Form;

/**
 * Class RegistrationForm
 * @package Perevorot\Users\Components
 */
class Contracts extends ComponentBase
{
    use CurrentLocale, ContractsTrait, ContractsUtils;

    /**
     * Template for step 1
     */
    const STEP_ONE_TEMPLATE = '@contracts/_step1.htm';

    /**
     * Template for step 2
     */
    const STEP_TWO_TEMPLATE = '@contracts/_step2.htm';

    /**
     * Template for step 3
     */
    const STEP_THREE_TEMPLATE = '@contracts/_step3.htm';

    /**
     * Template for step 4
     */
    const STEP_FOUR_TEMPLATE = '@contracts/_step4.htm';

    /**
     * @var
     */
    private $step;
    public $siteLocale;
    private $setting;
    private $sessionKey;
    private $user;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Contract',
            'description' => 'Contract edit by user'
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
        $this->sessionKey = Form::sessionKey();
        $this->siteLocale = $this->getCurrentLocale();
        $this->setting = Setting::instance();
        $this->user = Auth::getUser();

        $component = $this->addComponent(
            'Perevorot\Uploader\Components\FileUploader',
            'fileUploader',
            [
                'is_delete' => true,
                'is_edit' => true,
                'maxSize' => @$this->setting->value['max_file_size'],
                'deferredBinding' => true,
                'fileTypes' => @$this->setting->value['file_types'],
            ]
        );

        if(!$contract = $this->getContract()) {
            $contract = new Contract();
        }

        if(isset($contract->id) && !$contract->change_id){
            $contract->clearChangeDocuments();
        }

        $component->bindModel('changeDocuments', $contract);
    }

    /**
     * @return array|RedirectResponse|mixed|string
     */
    public function onRun()
    {

        if (!$this->user || !$this->user->checkGroup('customer'))
        {
            return redirect()->to($this->siteLocale);
        }

        if($id = $this->param('contractID')) {

            $params = ['cid' => $id, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1];

            if(($contract = Contract::getData($params)) instanceof Contract) {
                if(Session::get('contract.id') != $contract->id) {
                    Session::put('contract.id', $contract->id);
                    Session::put('contract.update', 1);
                    Session::put('contract.session', 1);
                }
            } else {
                $this->clearSession();
                return redirect()->to($this->siteLocale . 'tender/search#contracts');
            }
        }
        elseif(Session::get('contract.update'))
        {
            $this->clearSession();
        }

        /** @var int step */
        $this->step = Session::get('contract.session', 1);

        if($this->step == 1) {
            $contract = $this->getContract();
            $json = $contract->getJson();

            if (sizeof($json) <= 0 || !isset($json->items) || !isset($json->value) || !isset($json->value->amount) || !isset($json->period)) {
                unset($params['id']);
                $params['tender_system_id'] = $contract['tender_id'];

                $tender = Tender::getData($params);

                if(!$tender) {
                    return redirect()->to($this->siteLocale . 'tender/search#contracts');
                }

                $api = new Api();
                $_c = $api->getContract($tender, $contract);

                if(!is_object($json)) {
                    $json = new \stdClass();
                }

                if(!isset($json->period)) {
                    $json->period = new \stdClass();
                }

                $json->period = $_c->data->period;

                if(!isset($json->value)) {
                    $json->value = new \stdClass();
                }

                $json->value = $_c->data->value;

                if(!isset($json->items)) {
                    $json->items = new \stdClass();
                }

                $json->items = $_c->data->items;
                $contract->json = json_encode($json);
                $contract->status = $_c->data->status;
                $contract->save();
            }
        }

        if($contract->status != 'active') {
            return redirect()->to($this->siteLocale . 'tender/search#contracts');
        }

        $response = $this->contract();

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

        if ($result instanceof RedirectResponse) {
            return $result;
        }
        elseif ($result === false)
        {
            return [
                '#contract-access-error'=>$this->renderPartial('@messages/contract_access_error')
            ];
        }
        elseif ($result === true)
        {
            $contract = $this->getContract();
            $this->clearSession();
            return redirect()->to($this->siteLocale . 'contract/'.$contract->cid);
        }

        if (!array_key_exists('template', $result)) {
            throw new \Exception('Поле `template` должно быть объявлено');
        }

        $template = $result['template'];
        $params = (array_key_exists('params', $result)) ? $result['params'] : [];
        $params['CurrentLocale'] = $this->getCurrentLocaleWithoutSlash();
        $params['siteLocale'] = $this->siteLocale;

        return [
            '#contract-edit' => $this->renderPartial($template, $params),
        ];
    }

    /**
     * @return mixed
     */
    public function onReturnBack()
    {
        $step = (int) post('step');

        return [
            '#contract-edit' => $this->renderTemplateByStep($step),
        ];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function contract()
    {
        return $this->renderTemplateByStep($this->step);
    }
}
