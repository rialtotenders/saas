<?php namespace Perevorot\Form\Components;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Perevorot\Form\Classes\Parser;
use Perevorot\Form\Components\Traits\ApplicationHandlers;
use Perevorot\Form\Components\Traits\ApplicationStepFactory;
use Perevorot\Form\Components\Traits\ApplicationStepUtils;
use Perevorot\Form\Components\Traits\ApplicationUpdateSessionUtils;
use Perevorot\Form\Components\Traits\ApplicationUpdateStepFactory;
use Perevorot\Form\Components\Traits\ApplicationUpdateStepUtils;
use Perevorot\Form\Components\Traits\CustomValidatorMessages;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\AccessToTenders;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Form\Classes\Api;
use Illuminate\Http\RedirectResponse;
use October\Rain\Support\Facades\Form;
use Event;

class ApplicationUpdater extends ComponentBase
{
    use AccessToTenders, CustomValidatorMessages, ApplicationUpdateStepFactory, ApplicationHandlers, ApplicationStepUtils {
        onReturnBack as onReturnBackOld;
        onSubmitMultiLotApplication as onSubmitMultiLotApplicationOld;
    }
    use ApplicationUpdateSessionUtils;

    /** @var string */
    const FIRST_STEP_TEMPLATE = 'applicationupdater/multi_lot/_step_1.htm';
    const SECOND_STEP_TEMPLATE = 'applicationupdater/multi_lot/_step_2.htm';
    const LOT_STEP_TEMPLATE = 'applicationupdater/multi_lot/_step_lot.htm';
    const LAST_STEP_TEMPLATE = 'applicationupdater/multi_lot/_step_last.htm';


    private $application;
    private $tender;
    public $siteLocale;
    public $user;
    public $gos_tender;
    public $sessionKey;
    private $api;

    /**
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'ApplicationUpdater Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $redirect = $this->redirectTo();

        if($redirect instanceof RedirectResponse)
        {
            return $redirect;
        }

        if(!$this->application instanceof Application || $this->tender->status != 'active.tendering')
        {
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }

        if($this->user->is_test && (!isset($this->tender->mode ) || $this->tender->mode != 'test')) {
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }

        $user_tender = Tender::getData(['tender_id' => $this->getTenderId(), 'gov' => $this->user->is_gov, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

        if($user_tender instanceof Tender) {
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }
    }

    public function init()
    {
        $this->siteLocale = $this->getCurrentLocale();
        $setting = Setting::instance();
        $this->user = Auth::getUser();
        $parser = Parser::instance();
        $this->sessionKey = Form::sessionKey();

        $this->tender = $parser->tender_parse(
            $this->property('tender_id')
        );

        $this->gos_tender = stripos($this->tender->tenderID, 'R-') === FALSE;
        $this->api=new Api($this->gos_tender);
        $this->application = Application::getData(['tender_id' => $this->tender->id, 'user_id' => $this->user->id, 'test' => $this->user->is_test, 'limit' => 1]);

        if(!$this->application instanceof Application) {
            return false;
        }

        if(empty(post()) && $this->getStepNumber() <= 1){
            $this->application->clearChangeDocuments();
        }

        $tender_TS = $this->tender->procurementMethodType == 'aboveThresholdTS';

        if(empty(post()) && $this->application) {
            $this->application->clearUnattachedFiles($this->user->id);
        }

        if($this->getStepNumber() == 2 || isset($_GET['step'])) {

            $component = $this->addComponent(
                'Perevorot\Uploader\Components\FileUploader',
                'fileUploader',
                [
                    'exclude' => $tender_TS ? 'commercialProposal' : null,
                    'is_delete' => false,
                    'is_edit' => true,
                    'maxSize' => @$setting->value['max_file_size'],
                    'deferredBinding' => true,
                    'fileTypes' => @$setting->value['file_types'],
                ]
            );

            $component->bindModel('documents', $this->application);

            if($tender_TS) {

                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader_financial',
                    [
                        'exclude' => 'all',
                        'docType' => 2,
                        'is_delete' => false,
                        'is_edit' => true,
                        'maxSize' => @$setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$setting->value['file_types'],
                    ]
                );

                $component->bindModel('documents', $this->application);
            }
        }
        elseif($this->getStepNumber() == 3) {

            if(!$this->getLotStep() || !isset($this->application->lot_id)) {
                $this->setLotStep(0);
            }

            $applications = $this->getApplications();

            $component = $this->addComponent(
                'Perevorot\Uploader\Components\FileUploader',
                'fileUploader',
                [
                    'exclude' => $tender_TS ? 'commercialProposal' : null,
                    'byLot' => $applications[$this->getLotStep()]->lot_id,
                    'is_delete' => false,
                    'is_edit' => true,
                    'maxSize' => @$setting->value['max_file_size'],
                    'deferredBinding' => true,
                    'fileTypes' => @$setting->value['file_types'],
                ]
            );

            $component->bindModel('documents', $this->application);

            if($tender_TS) {

                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader_financial',
                    [
                        'exclude' => 'all',
                        'byLot' => $this->application->lot_id,
                        'docType' => 2,
                        'is_delete' => false,
                        'is_edit' => true,
                        'maxSize' => @$setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$setting->value['file_types'],
                    ]
                );

                $component->bindModel('documents', $this->application);
            }
        } elseif($this->getStepNumber() == 4) {

            foreach($this->getApplications() AS $_app) {
                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader_'.$_app->lot_id,
                    [
                        'docType' => [1,2],
                        'exclude' => [1 => 'commercialProposal', 2 => 'all'],
                        'byLot' => $_app->lot_id,
                        'is_delete' => false,
                        'is_edit' => true,
                        'maxSize' => @$setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$setting->value['file_types'],
                    ]
                );

                $component->bindModel('documents', $this->application);
            }

            $component = $this->addComponent(
                'Perevorot\Uploader\Components\FileUploader',
                'fileUploader',
                [
                    'docType' => [1,2],
                    'exclude' => [1 => 'commercialProposal', 2 => 'all'],
                    'is_delete' => false,
                    'is_edit' => true,
                    'maxSize' => @$setting->value['max_file_size'],
                    'deferredBinding' => true,
                    'fileTypes' => @$setting->value['file_types'],
                ]
            );

            $component->bindModel('documents', $this->application);
        }
    }

    public function onRender()
    {
        $this->addJs('assets/js/application-validation.js');
        //$this->setStepNumber(1);
        //$this->setApplicationCreatedStatus(false);
        //$this->setMemoryApplications([]);

        if(!$this->getCurrentStep()) {
            $this->setStepNumber(1);
            $this->setLotStep(0);
        }

        $parameters = $this->getDefaultParameters();

        if ($this->isMultiLot()) {
            $data = $this->processRenderStepFactory(
                $this->getCurrentStep()
            );
            $parameters = array_merge(
                $parameters,
                $data['params']
            );

            return $this->renderPartial(
                $data['template'],
                $parameters
            );
        } else {

            if(!isset($_GET['step'])) {

                if($this->application->tender_features) {
                    $this->application->tender_features = (array)json_decode($this->application->tender_features);
                }

                return $this->renderPartial('applicationcreater/single/create.htm', array_merge([
                   'app' => $this->application,
                    'edit' => true,
               ], $parameters));
            }
            else {

                $document_types = $this->tender->procurementMethodType == 'aboveThresholdTS' ? Procurement::getData('bid_document_types') : [];

                return $this->renderPartial('applicationupdater/single/update.htm', array_merge([
                    'app' => $this->application,
                    'document_types' => $document_types,
                ], $parameters));
            }
        }
    }

    public function onSave()
    {
        if($this->tender->status != 'active.tendering' || !$this->accessToBid())
        {
            return [
                '#application-access-error' => $this->renderPartial('@messages/application_access_error')
            ];
        }

        $data = post();

        if(!isset($_GET['step'])) {

            if(!empty(post('features'))) {
                $_price_key = 'feature_price';
            } else {
                $_price_key = 'price';
            }

            $validator = Validator::make($data, [
                $_price_key => 'required|numeric|between:0.1,'.$this->tender->value->amount,//.'|max:'.$this->application->price
            ], ValidationMessages::generateCustomMessages($this->customMessages, 'application'));

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        $form = $this->application;
        $update_data = false;

        if(!isset($_GET['step'])) {
            $form->price = (float) $data['price'];
            $form->feature_price = !empty(post('feature_price')) ? post('feature_price') : null;
            $form->tender_features = !empty(post('features')) ? json_encode(post('features')) : null;
            $update_data = true;
        }

        $this->saveDocType();

        if($this->api->bidSingleLot($form, $this->tender, $update_data)){
            $form->save(null, post('_session_key', $this->sessionKey));

            Event::fire('perevorot.form.bid', [
                'tender' => $this->tender,
                'user' => $form,
                'type' => 'updated',
            ], true);

             if(!isset($_GET['step'])) {
                return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId() . "/application/update?step=2");
             }   else {
                return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
             }
        }else{
            return [
                '#application-access-error'=>$this->renderPartial('@messages/application_access_error')
            ];
        }

        return [
            '#application' => $this->renderPartial('applicationupdater/single/success.htm', $this->getDefaultParameters())
        ];
    }

    /**
     * @return mixed
     */
    public function onReturnBack()
    {
        if($this->getStepNumber() == 3 && $this->getLotStep() > 0) {
            $this->setLotStep(
                $this->getLotStep() - 1
            );
        } else {

            if($this->getStepNumber() == 4) {
                $this->setLotStep(
                    $this->getLotStep() - 1
                );
            }

            $this->setStepNumber(
                $this->getStepNumber() - 1
            );
        }

        /**
         * Берем данные с памяти уже пройденные лоты
         */
        /*$memory = $this->getMemoryApplications();
        $application = array_pop($memory);
        $this->setMemoryApplications($memory);

        if ($application) {
            $applications = array_merge([
                $application
            ], $this->getApplications());

            $this->setApplications(
                $applications
            );
        }*/

        return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId() . '/application/update');
    }

    /**
     * @return array
     */
    public function onSubmitMultiLotApplication()
    {
        $r = $this->processStepFactory(
            $this->getCurrentStep()
        );

        if($r instanceof RedirectResponse) {
            return $r;
        }

        return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId() . '/application/update');
    }
}
