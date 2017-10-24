<?php

namespace Perevorot\Users\Components;

use Cms\Classes\ComponentBase;
use DB;
use Illuminate\Support\Facades\Event;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Payment;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Models\MessageTenderCancel;
use Perevorot\Users\Traits\TenderCancellingValidator;
use Perevorot\Users\Facades\Auth;
use Request;
use Cache;
use Yaml;
use Perevorot\Form\Classes\Api;
use App;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use System\Models\File;

class TenderCancelling extends ComponentBase
{
    use CurrentLocale, TenderCancellingValidator;

    var $search_type;
    var $item;
    public $user_tender;
    public $siteLocale;
    private $setting;
    private $lot_id;
    private $messages;

    public function onTenderCancelling()
    {
        if($this->user_tender) {

            $validator = Validator::make(post(), $this->rules, ValidationMessages::generateCustomMessages($this->customMessages, 'tender_cancelling'));

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $api = new Api();

            if($api->cancellingTender($this->user_tender, post('reason'), $this->lot_id))
            {
                if(!$this->lot_id) {
                    $apps = $this->user_tender->applications;
                } else {
                    $apps = $this->user_tender->applications()->byLot($this->lot_id)->get();
                }

                foreach($apps AS $application) {
                    foreach ($application->bidDocuments as $document) {
                        DB::statement('DELETE FROM perevorot_rialtotender_application_files WHERE id=' . $document->id);

                        if($file = File::find($document->system_file_id)) {
                            $file->delete();
                        }
                    }

                    $application->user->pay($this->item, Payment::PAYMENT_TYPE_CANCELED_BID, $application);
                    $application->delete();
                }

                Event::fire('perevorot.users.tender', [
                    'tender' => $this->user_tender,
                    'type' => 'cancelled',
                    'lot' => $this->lot_id,
                ], true);

                Event::fire('perevorot.rialtotender.tender_changed', [
                    'tender' => $this->user_tender,
                    'type' => 'cancelled',
                    'lot' => $this->lot_id,
                ], true);

                return redirect()->to($this->siteLocale.'tender/'.$this->user_tender->tender_id);
            }
        }
    }

    public function defineProperties()
    {
        return [
            'tender_id' => [
                'label' => 'Идентификатор тендера',
            ],
            'lot_id' => [
                'label' => 'Идентификатор лота'
            ]
        ];
    }

    public function componentDetails()
    {
        return [
            'name' => 'Отмена тендера',
            'description' => '',
            'icon'=>'icon-files-o',
        ];
    }

    public function init()
    {
        $this->messages = MessageTenderCancel::instance();
        $this->setting = Setting::instance();
        $this->siteLocale = $this->getCurrentLocale();
        $tenderID = $this->property('tender_id');
        $parser = Parser::instance();
        $this->item = $parser->tender_parse($tenderID);
        $this->lot_id = $this->property('lot_id');

        if(Auth::getUser()) {
            $this->user_tender = Tender::getData(['tender_id' => $this->item->tenderID, 'gov' => Auth::getUser()->is_gov, 'test' => Auth::getUser()->is_test, 'user_id' => Auth::getUser()->id, 'limit' => 1]);
        }
        else {
            $this->user_tender = null;
        }

        if($this->user_tender) {
            $component = $this->addComponent(
                'Perevorot\Uploader\Components\FileUploader',
                'fileUploader',
                [
                    'byLot' => $this->lot_id,
                    'is_delete' => true,
                    'deferredBinding' => true,
                    'maxSize' => $this->setting->get_value('max_file_size'),
                    'fileTypes' => $this->setting->get_value('file_types'),
                ]
            );

            $component->bindModel('cancellingDocuments', $this->user_tender);
        }
    }

    public function onRun()
    {

        if(!$this->user_tender || in_array($this->item->status, ['unsuccessful', 'cancelled', 'complete'])) {
            return redirect()->to($this->siteLocale.'tender/'.$this->item->tenderID);
        }

        if($this->lot_id) {
            $lot_id = $this->lot_id;
            $lot = array_first($this->item->lots, function($lot, $key) use($lot_id) {
                return $lot->id == $lot_id;
            });

            if($lot) {
                $this->page['lot_title'] = $lot->title;
            }
        }

        $this->page['lot'] = $this->lot_id;
        $this->page['tender_id'] = $this->item->tenderID;
        $this->page['message_text'] = $this->getText($this->lot_id);
        $this->page['message_title'] = $this->getHeader($this->lot_id);
    }

    public function onRender()
    {
        $this->addJs('assets/js/tender-validation.js');

        return $this->renderPartial('tendercancelling/index.htm');
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getText($lot = false)
    {
        return ($this->messages ? $this->messages->{'step' . ($lot ? '-lot' : '')} : '');
    }

    /**
     * @param $step
     * @return mixed
     */
    private function getHeader($lot = false)
    {
        return ($this->messages ? $this->messages->{'header' . ($lot ? '-lot' : '')} : '');
    }
}
