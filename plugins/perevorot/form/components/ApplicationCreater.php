<?php

namespace Perevorot\Form\Components;

use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Illuminate\Http\RedirectResponse;
use October\Rain\Support\Facades\Form;
use Illuminate\Support\Facades\Redirect;
use Perevorot\Form\Classes\Parser;
use Perevorot\Form\Components\Traits\ApplicationCreateSessionUtils;
use Perevorot\Form\Components\Traits\ApplicationHandlers;
use Perevorot\Form\Components\Traits\ApplicationStepFactory;
use Perevorot\Form\Components\Traits\ApplicationStepUtils;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tariff;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\AccessToTenders;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\Message;
use RainLab\Translate\Classes\Translator;

/**
 * Class ApplicationCreater
 * @package Perevorot\Form\Components
 */
class ApplicationCreater extends ComponentBase
{
    use AccessToTenders, ApplicationStepFactory, ApplicationStepUtils, ApplicationHandlers, ApplicationCreateSessionUtils;

    /** @var string */
    const FIRST_STEP_TEMPLATE = 'applicationcreater/multi_lot/_step_1.htm';
    const SECOND_STEP_TEMPLATE = 'applicationcreater/multi_lot/_step_2.htm';
    const LOT_STEP_TEMPLATE = 'applicationcreater/multi_lot/_step_lot.htm';
    const LAST_STEP_TEMPLATE = 'applicationcreater/multi_lot/_step_last.htm';

    private $tender;
    public $siteLocale;
    public $user;
    public $gos_tender;
    public $setting;
    public $sessionKey;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'ApplicationCreater Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @return array
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->sessionKey = Form::sessionKey();
        $this->siteLocale = $this->getCurrentLocale();
        $parser = Parser::instance();
        $setting = Setting::instance();
        $this->user = Auth::getUser();
        $this->setting = Setting::instance();

        if(!$this->user) {
            return false;
        }

        $this->tender = $parser->tender_parse(
            $this->param('tenderID')
        );

        $this->gos_tender = stripos($this->tender->tenderID, 'R-') === FALSE;

        if ($this->isMultiLot()) {
            $applications = $this->getApplications();

            if(is_array($applications) && !empty($applications)) {
                $application = $applications[0];

                //$app = Application::getData(['id' => $application->application_id, 'tender_id' => $this->tender->id, 'user_id' => $this->user->id, 'test' => $this->user->is_test, 'limit' => 1]);
                $app = Application::find($application->application_id);

                if(!$app instanceof Application) {
                    $app = new Application();
                    $this->removeApplications();
                    $this->setStepNumber(1);
                    $this->setLotStep(0);
                }

            } else {
                $this->removeApplications();
                $this->setStepNumber(1);
                $this->setLotStep(0);
                $app = new Application();
            }

            if(empty(post()) && $app) {
                $app->clearUnattachedFiles($this->user->id);
            }

            if(!$this->isApplicationCreated() || !isset($app->id)) {
                $app->clearDocuments();
            }
            elseif(isset($app->id) && count($app->bidDocuments) <= 0) {
                //$app->clearDocuments();
            }

            $tender_TS = $this->tender->procurementMethodType == 'aboveThresholdTS';

            if($this->getStepNumber() == 2) {
                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader',
                    [
                        'exclude' => $tender_TS ? 'commercialProposal' : null,
                        'is_delete' => true,
                        'is_edit' => false,
                        'maxSize' => @$setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$setting->value['file_types'],
                    ]
                );

                $component->bindModel('documents', $app);

                if($tender_TS) {

                    $component = $this->addComponent(
                        'Perevorot\Uploader\Components\FileUploader',
                        'fileUploader_financial',
                        [
                            'exclude' => 'all',
                            'docType' => 2,
                            'is_delete' => true,
                            'is_edit' => false,
                            'maxSize' => @$setting->value['max_file_size'],
                            'deferredBinding' => true,
                            'fileTypes' => @$setting->value['file_types'],
                        ]
                    );

                    $component->bindModel('documents', $app);
                }
            }
            elseif($this->getStepNumber() == 3) {
                if(!$this->getLotStep() || !isset($applications[$this->getLotStep()]->lot_id)) {
                    $this->setLotStep(0);
                }

                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader',
                    [
                        'exclude' => $tender_TS ? 'commercialProposal' : null,
                        'byLot' => $applications[$this->getLotStep()]->lot_id,
                        'is_delete' => true,
                        'is_edit' => false,
                        'maxSize' => @$setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$setting->value['file_types'],
                    ]
                );

                $component->bindModel('documents', $app);

                if($tender_TS) {

                    $component = $this->addComponent(
                        'Perevorot\Uploader\Components\FileUploader',
                        'fileUploader_financial',
                        [
                            'exclude' => 'all',
                            'byLot' => $applications[$this->getLotStep()]->lot_id,
                            'docType' => 2,
                            'is_delete' => true,
                            'is_edit' => false,
                            'maxSize' => @$setting->value['max_file_size'],
                            'deferredBinding' => true,
                            'fileTypes' => @$setting->value['file_types'],
                        ]
                    );

                    $component->bindModel('documents', $app);
                }

            } elseif($this->getStepNumber() == 4) {
                foreach($applications AS $_app) {
                    $component = $this->addComponent(
                        'Perevorot\Uploader\Components\FileUploader',
                        'fileUploader_'.$_app->lot_id,
                        [
                            'docType' => [1,2],
                            'exclude' => [1 => 'commercialProposal', 2 => 'all'],
                            'byLot' => $_app->lot_id,
                            'is_delete' => true,
                            'is_edit' => false,
                            'maxSize' => @$setting->value['max_file_size'],
                            'deferredBinding' => true,
                            'fileTypes' => @$setting->value['file_types'],
                        ]
                    );

                    $component->bindModel('documents', $app);
                }

                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader',
                    [
                        'docType' => [1,2],
                        'exclude' => [1 => 'commercialProposal', 2 => 'all'],
                        'is_delete' => true,
                        'is_edit' => false,
                        'maxSize' => @$setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$setting->value['file_types'],
                    ]
                );

                $component->bindModel('documents', $app);
            }
        }
    }

    /**
     * @return void
     */
    public function onRun()
    {
        $redirect = $this->redirectTo();

        if($redirect instanceof RedirectResponse)
        {
            return $redirect;
        }

        if(!$this->user) {
            return redirect()->back();
        }

        $access_to_bid = ($this->tender->procurementMethodType != 'aboveThresholdTS' || ($this->setting->checkAccess('bidTwoStage') && $this->tender->procurementMethodType == 'aboveThresholdTS'));

        if($this->tender->status != 'active.tendering' || !$access_to_bid)
        {
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }

        if($this->user->is_test && (!isset($this->tender->mode ) || $this->tender->mode != 'test')) {
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }

        $user_tender = Tender::getData(['tender_id' => $this->getTenderId(), 'gov' => $this->user->is_gov, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

        if(!$this->user || $this->checkExistsApplication() || $user_tender instanceof Tender){
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }

        $tariff = Tariff::getTariff(['is_gov' => stripos($this->tender->tenderID, 'R-') === FALSE, 'price' => $this->tender->value->amount, 'currency' => $this->tender->value->currency]);

        if(!$this->user->checkMoney($tariff) && $this->tender->value->amount < 999999999)
        {
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }
    }

    /**
     * @return mixed
     */
    public function onRender()
    {
        $this->addJs('assets/js/application-validation.js');

        $parameters = $this->getDefaultParameters();

        if(!$this->getCurrentStep()) {
            $this->setStepNumber(1);
            $this->setLotStep(0);
        }

        if ($this->isMultiLot()) {
            $data = $this->processRenderStepFactory(
                $this->getCurrentStep()
            );
            $parameters = array_merge(
                $parameters,
                $data['params']
            );
            $parameters['step'] = $this->getCurrentStep();

            return $this->renderPartial(
                $data['template'],
                $parameters
            );
        } else {
            return $this->renderPartial(
                'applicationcreater/single/create.htm',
                $parameters
            );
        }
    }
}
