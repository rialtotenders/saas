<?php

namespace Perevorot\Users\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Traits\TenderTrait;
use Perevorot\Users\Traits\TenderUtils;
use Perevorot\Users\Facades\Auth;
use October\Rain\Support\Facades\Form;
use October\Rain\Exception\ValidationException;

/**
 * Class RegistrationForm
 * @package Perevorot\Users\Components
 */
class TenderCreate extends ComponentBase
{
    use CurrentLocale, TenderTrait, TenderUtils;

    /**
     * Template for step 1
     */
    const STEP_ONE_TEMPLATE = '@tendercreate/_step1.htm';

    /**
     * Template for step 2
     */
    const STEP_TWO_TEMPLATE = '@tendercreate/_step2.htm';

    /**
     * Template for step 3
     */
    const STEP_THREE_TEMPLATE = '@tendercreate/_step3.htm';

    /**
     * Template for step 4
     */
    const STEP_FOUR_TEMPLATE = '@tendercreate/_step4.htm';

    /**
     * Template for step 5
     */
    const STEP_FIVE_TEMPLATE = '@tendercreate/_step5.htm';

    /**
     * Template for step 6
     */
    const STEP_SIX_TEMPLATE = '@tendercreate/_step6.htm';

    /**
     * Template for step 7
     */
    const STEP_SEVEN_TEMPLATE = '@tendercreate/_step7.htm';

    /**
     * Template for step criteria
     */
    const STEP_ONE_A_TEMPLATE = '@tendercreate/_step1a.htm';

    /**
     * @var
     */
    private $step;
    public $siteLocale;
    private $setting;
    private $sessionKey;
    public $user;
    public $is_gov;
    public $_form_validation;
    public $source;
    public $_form_validation_field;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'TenderCreate',
            'description' => 'Tender create by user'
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
        $this->sessionKey = Form::sessionKey();
        $this->user = Auth::getUser();
        $this->is_gov = $this->user->is_gov ? 'gov-' : null;
        $this->source = $this->param('source');

        if(!$tender = $this->getTender()) {
            $tender = new Tender();
            $this->clearSession();
            $tender->clearDocuments();
        }
        elseif (!Session::get('tender.update')) {
            Session::put('tender.session', 1);
        }

        if (empty(post())) {
            $tender->clearChangeDocuments();
        }

        foreach(range(0, 30) as $index) {
            $component = $this->addComponent(
                'Perevorot\Uploader\Components\FileUploader',
                'fileUploader_lot_' . $index,
                [
                    'byLot' => $index,
                    'is_delete' => true,
                    'is_edit' => (boolean)(count($tender->tenderDocuments)),
                    'maxSize' => $this->setting->get_value('max_file_size'),
                    'deferredBinding' => true,
                    'fileTypes' => $this->setting->get_value('file_types'),
                ]
            );

            $component->bindModel('documents', $tender);
        }

        $component = $this->addComponent(
            'Perevorot\Uploader\Components\FileUploader',
            'fileUploader',
            [
                'is_delete' => true,
                'is_edit' => (boolean)(count($tender->tenderDocuments)),
                'maxSize' => $this->setting->get_value('max_file_size'),
                'deferredBinding' => true,
                'fileTypes' => $this->setting->get_value('file_types'),
            ]
        );

        $component->bindModel('documents', $tender);
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

        /*
        if($this->is_gov && $this->source != 'gov') {
            return redirect()->to($this->getCurrentLocale().'tender/create/gov');
        }elseif(!$this->is_gov && $this->source == 'gov') {
            return redirect()->to($this->getCurrentLocale().'tender/create');
        }
        */

        if($tenderID = $this->param('tenderID')) {

            $params = ['gov' => $this->user->is_gov, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1];

            if(is_numeric($tenderID)) {
                $params['id'] = $tenderID;
            } else {
                $params['tender_id'] = $tenderID;
            }

            if(($tender = Tender::getData($params)) instanceof Tender) {
                if(Session::get('tender.id') != $tender->id) {
                    Session::put('tender.id', $tender->id);
                    Session::put('tender.update', 1);
                    Session::put('tender.session', 1);
                }
            } else {
                $this->clearSession();
                return redirect()->to($this->siteLocale . 'tender/search#'.$this->is_gov.'tenders');
            }

            if(!is_numeric($tenderID)) {

                $parser = Parser::instance();
                $tender = $parser->tender_parse($tenderID, 'tid', $tender->is_test);

                if(($tender->procurementMethodType != 'aboveThresholdTS' && !in_array($tender->status, ['active.enquiries'])) || ($tender->procurementMethodType == 'aboveThresholdTS' && !in_array($tender->status, ['active.enquiries', 'active.tendering'])))
                {
                    $this->clearSession();
                    return redirect()->to($this->siteLocale . 'tender/search#'.$this->is_gov.'tenders');
                }
            }
        }
        elseif(Session::get('tender.update'))
        {
            $this->clearSession();
        }

        /** @var int step */
        $this->step = Session::get('tender.session', 1);

        if(!empty(get('_v')) && get('_v') == 1) {
            return $this->onHandleForm();
            exit;
        }

        $response = $this->tender();

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
        $this->_form_validation = post('_validation') == 1;
        $this->_form_validation_field = $this->_form_validation ? explode(',', post('_validation_field')) : false;
        $result = $this->processStepFactory($step, ($step != 1));

        if($this->_form_validation) {
            return Response::make(json_encode($result), 406);
        }
        elseif((boolean)post('save')) {
            $this->clearSession(false);
            return redirect()->to($this->siteLocale . 'tender/search#'.$this->is_gov.'tenders');
        }
        elseif ($result instanceof RedirectResponse) {
            return $result;
        }
        elseif ($result === false)
        {
            return [
                '#tender-access-error'=>$this->renderPartial('@messages/tender_access_error')
            ];
        }
        elseif ($result === true)
        {
            return redirect()->to($this->siteLocale . 'tender/search#'.$this->is_gov.'tenders');
        }

        if (!array_key_exists('template', $result)) {
            throw new \Exception('Поле `template` должно быть объявлено');
        }

        if(!empty(post('next_step'))) {
            return $this->onReturnBack();
        }

        $template = $result['template'];
        $params = (array_key_exists('params', $result)) ? $result['params'] : [];
        $params['CurrentLocale'] = $this->getCurrentLocaleWithoutSlash();
        $params['siteLocale'] = $this->siteLocale;
        $params['show_features'] = $this->setting->checkAccess('criteria');
        $params['show_lots'] = $this->setting->checkAccess('lots');
        $params['file_types'] = $this->processFileTypes($this->setting->get_value('file_types'));
        $params['_update'] = Session::get('tender.update') ? Session::get('tender.id') : '';

        return [
            '#tender-content' => $this->renderPartial($template, $params),
        ];
    }

    /**
     * @return mixed
     */
    public function onReturnBack()
    {
        $step = !empty(post('next_step')) ? (int)post('next_step') : (int)post('step');

        return [
            '#tender-content' => $this->renderTemplateByStep($step),
        ];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function tender()
    {
        return $this->renderTemplateByStep($this->step);
    }
}
