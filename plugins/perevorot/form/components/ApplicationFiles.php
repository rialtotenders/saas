<?php namespace Perevorot\Form\Components;

use Perevorot\Form\Classes\Api;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Cms\Classes\ComponentBase;
use Perevorot\Form\Components\Traits\ApplicationStepUtils;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Users\Traits\UserSetting;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Users\Facades\Auth;
use Perevorot\Rialtotender\Models\Application;
use Redirect;
use System\Models\File;

class ApplicationFiles extends ComponentBase
{
    use CurrentLocale, UserSetting, ApplicationStepUtils;

    private $user;
    private $user_mode;
    private $setting;
    private $api;
    private $tender;
    private $applications;
    private $application;
    public $siteLocale;
    private $error;
    public $gos_tender;

    public function componentDetails()
    {
        return [
            'name'        => 'Update bid files',
            'description' => ''
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {

        $this->setting = Setting::instance();
        $this->siteLocale = $this->getCurrentLocale();
        $this->user = Auth::getUser();
        $this->user_mode = $this->checkUserMode($this->user);

        $this->tender = Parser::instance()->tender_parse(
            $this->param('tenderID')
        );

        if(!$this->tender) {
            $this->error = true;
            return false;
        }

        $this->gos_tender = stripos($this->tender->tenderID, 'R-') === FALSE;
        $this->api=new Api($this->gos_tender);

        $this->applications = Application::getData([
            'tender_id' => $this->tender->id,
            'user_id' => $this->user->id,
            'test' => $this->user->is_test,
        ]);

        if(count($this->applications) <= 0) {
            $this->error = true;
            return false;
        }

        $this->application = $this->applications[0];

        if(empty(post())){
            $this->application->clearChangeDocuments();
        }

        $component=$this->addComponent(
            'Perevorot\Uploader\Components\FileUploader',
            'fileUploader', [
                'is_delete' => $this->tender->procurementMethodType != 'aboveThresholdTS',
                'is_edit' => $this->tender->procurementMethodType != 'aboveThresholdTS',
                'maxSize' => @$this->setting->value['max_file_size'],
                'deferredBinding' => true,
                'fileTypes' => @$this->setting->value['file_types'],
            ]
        );

        $component->bindModel('qualificationDocuments', $this->application);

        if(isset($this->tender->lots)) {

            foreach ($this->applications AS $k => $app) {

                $item = array_first($this->tender->lots, function ($lot, $k) use ($app) {
                    return $lot->id === $app->lot_id;
                });

                if ($item) {
                    $this->applications[$k]->lot_title = $item->title;
                }
            }
        }
    }

    public function onRun()
    {
        if(!$this->access() || $this->error) {
            return Redirect::to($this->siteLocale.'tender/' . $this->getTenderId());
        }
    }

    public function onRender()
    {
        $parameters = $this->getDefaultParameters();
        $document_types = $this->tender->procurementMethodType == 'aboveThresholdTS' ? Procurement::getData('bid_document_types') : [];

        return $this->renderPartial('applicationfiles/single/default.htm', array_merge([
            'app' => $this->application,
            'applications' => $this->applications,
            'documents' => $this->application->documents()->get(),
            'document_types' => $document_types,
        ], $parameters));
    }

    private function access()
    {
        $can=true;

        if(!in_array($this->tender->status, ['active.qualification'])) {
            $can=false;
        }

        if($this->user->is_test && (!isset($this->tender->mode ) || $this->tender->mode != 'test')) {
            $can=false;
        }
        //$can=true;
        return $can;
    }

    public function onSave()
    {
        if(!$this->access()) {
            return [
                '#application-access-error' => $this->renderPartial('@messages/application_access_error')
            ];
        }

        $lot_docs = [];
        $_files = [];

        if(!empty(post('lot_docs'))) {
            $lot_docs = post('lot_docs');
        }

        foreach($this->applications AS $app) {

            if(!$app->lot_id) { continue; }

            $files = [];

            if (!empty($lot_docs)) {
                $files = array_where($lot_docs, function ($lot_id, $file_id) use ($app) {
                    return $app->lot_id == $lot_id;
                });
            }

            if (!empty($files)) {
                foreach ($files as $id => $lot_id) {
                    $_files[$lot_id][] = File::find($id);
                }
            }
        }

        if(!empty($_files)) {
            foreach($this->applications AS $form) {
                if (isset($_files[$form->lot_id]) && $this->api->bidQualificationDocuments($form, $this->tender, $_files[$form->lot_id], $form->lot_id)) {

                    if (isset($form->lot_title)) {
                        unset($form->lot_title);
                    }

                    $form->save();
                }
            }
        }

        //foreach($this->applications AS $form) {
        $form = $this->applications[0];

        if($this->api->bidQualificationDocuments($form, $this->tender)) {

            if(isset($form->lot_title)) {
                unset($form->lot_title);
            }

            $form->save();
        }else{
            return [
                '#application-access-error'=>$this->renderPartial('@messages/application_access_error')
            ];
        }

       // }

        return Redirect::to($this->siteLocale . 'tender/' . $this->getTenderId());
    }
}
