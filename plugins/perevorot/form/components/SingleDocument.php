<?php namespace Perevorot\Form\Components;

use Illuminate\Support\Facades\Mail;
use October\Rain\Support\Facades\Form;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Cms\Classes\Theme;
use DateTime;
use DB;
use Illuminate\Http\RedirectResponse;
use Perevorot\Form\Components\Traits\DataHelpers;
use Perevorot\Form\Classes\Parser;
use Perevorot\Form\Components\Traits\ContractsValidator;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Models\Area;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\ComplaintPeriod;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\Payment;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Rialtotender\Models\Qualification;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Status;
use Perevorot\Rialtotender\Models\Tariff;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Models\TenderFile;
use Perevorot\Rialtotender\Traits\AccessToTenders;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Models\MessageTenderPage;
use Perevorot\Users\Models\User;
use Request;
use Cache;
use System\Models\File;
use Yaml;
use Perevorot\Form\Classes\Api;
use Perevorot\Rialtotender\Models\Question;
use App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use October\Rain\Exception\ValidationException;
use Event;

class SingleDocument extends ComponentBase
{
    use AccessToTenders, DataHelpers;

    var $error;
    var $search_type;
    var $item;
    private $user_tender;
    public $award_bid;
    private $setting;
    public $user_contract;
    private $sessionKey;
    private $contract;
    private $user;
    private $messages;
    public $gos_tender;
    public $qualifications;
    public $qualification_next_status;
    public $hide_qualifications_buttons;

    public function componentDetails()
    {
        return [
            'name' => 'Один документ',
            'description' => '',
            'icon'=>'icon-files-o',
        ];
    }

    public function init()
    {
        $this->sessionKey = Form::sessionKey();
        $this->setting = Setting::instance();
        $segmets = count(Request::segments());
        $tenderID = Request::segment($segmets);
        $this->search_type = $type = Request::segment($segmets - 1)=='plan' ? 'plan' : 'tender';
        $page_type = $type.'_parse';
        $parser = Parser::instance();
        $test_mode = false;

        if($this->search_type == 'tender' && $tenderID) {
            $tender = Tender::getData(['tender_id' => $tenderID, 'limit' => 1]);

            if($tender instanceof Tender) {
                $test_mode = $tender->is_test;
            }
        } elseif($this->search_type == 'plan' && $tenderID) {
            $tender = Plan::getData(['plan_id' => $tenderID, 'limit' => 1]);

            if($tender instanceof Plan) {
                $test_mode = $tender->is_test;
            }
        }

        $this->item = $parser->{$page_type}($tenderID, ($this->search_type == 'tender' ? 'tid' : 'pid'), $test_mode);
        $this->user = Auth::getUser();

        Event::listen('seo.handle', function ($seo){
            $seo->setData([
                'tender' => $this->item,
            ]);
        });

        if(!$this->item) {
            return false;
        }

        if ($this->search_type == 'plan') {
            $this->gos_tender = stripos($this->item->planID, 'R-') === FALSE;
        } else {
            $this->gos_tender = stripos($this->item->tenderID, 'R-') === FALSE;
        }

        if($this->item && $this->user) {

            if ($this->search_type == 'plan') {

                $this->user_tender = Plan::getData(['plan_id' => $this->item->planID, 'gov' => $this->user->is_gov, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

                if (!isset($this->user_tender->id)) {
                    $this->user_tender = null;
                }

            } else {

                $this->user_tender = Tender::getData(['tender_id' => $this->item->tenderID, 'gov' => $this->user->is_gov, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);

                if (!isset($this->user_tender->id)) {
                    $this->user_tender = null;
                } else {

                    //active.awarded

                    if($award_id = Session::get("tender.{$this->user_tender->tender_system_id}.award_id")) {
                        foreach ($this->item->awards AS $award) {
                            if ($award_id == $award->id && $award->status != Session::get("tender.{$this->user_tender->tender_system_id}.award_status")) {
                                Session::remove("tender.{$this->user_tender->tender_system_id}.award_id");
                                Session::remove("tender.{$this->user_tender->tender_system_id}.award_status");
                            }
                        }
                    }

                    if (!$this->item->__isMultiLot && $this->user_tender && $this->item->status == 'active.pre-qualification' && !empty($this->item->__qualifications)) {

                        $this->qualifications = $this->getQualifications();

                        if(!$this->user_tender->is_close_qualification) {
                            $this->qualification_next_status = true;
                            $this->hide_qualifications_buttons = false;

                            foreach ($this->item->qualifications AS $q) {
                                if ($q->status == 'pending') {
                                    $this->qualification_next_status = false;
                                    break;
                                }
                            }
                        } else {
                            $this->qualification_next_status = false;
                            $this->hide_qualifications_buttons = true;
                        }

                        foreach($this->qualifications AS $q) {

                            if($q->status == 'pending') {

                                $component = $this->addComponent(
                                    'Perevorot\Uploader\Components\FileUploader',
                                    'fileUploader_qualifications_' . $q->qualification_id,
                                    [
                                        //'is_edit' => true,
                                        'isMultiPage' => true,
                                        'maxSize' => @$this->setting->value['max_file_size'],
                                        'deferredBinding' => true,
                                        'fileTypes' => @$this->setting->value['file_types'],
                                    ]
                                );

                                $component->bindModel('qualificationDocuments', $q);
                            }
                        }
                    }

                    if ($this->item->status == 'active.qualification' && !empty($this->item->awards)) {

                        $component = $this->addComponent(
                            'Perevorot\Uploader\Components\FileUploader',
                            'fileUploader_awards',
                            [
                                //'is_edit' => true,
                                //'isMultiPage' => true,
                                'maxSize' => @$this->setting->value['max_file_size'],
                                'deferredBinding' => true,
                                'fileTypes' => @$this->setting->value['file_types'],
                            ]
                        );

                        if(empty(post())) {
                            $this->user_tender->clearAwardDocuments();
                        }

                        $component->bindModel('awardDocuments', $this->user_tender);
                    }

                    if($this->item->status == 'complete') {
                        $this->contract = Contract::getData(['tender_id' => $this->item->id, 'limit' => 1]);

                        if(!$this->contract instanceof Contract) {
                            $this->contract = null;
                        }
                    }

                    if(!empty($this->item->__contracts) && $this->item->status != 'complete') {

                        $this->user_contract = Contract::getPendingContract($this->item->__contracts);

                        if(!$this->user_contract instanceof Contract) {
                            $this->user_contract = new Contract();
                        }

                        if (empty(post())) {
                            $this->user_contract->clearChangeDocuments();
                        }

                        $component = $this->addComponent(
                            'Perevorot\Uploader\Components\FileUploader',
                            'fileUploader_contracts',
                            [
                                //'isMultiPage' => true,
                                //'is_delete' => true,
                                'is_edit' => true,
                                'maxSize' => @$this->setting->value['max_file_size'],
                                'deferredBinding' => true,
                                'fileTypes' => @$this->setting->value['file_types'],
                            ]
                        );

                        $component->bindModel('contractDocuments', $this->user_contract);

                    }
                }
            }
        }
        else {
            $this->user_tender = null;
        }
    }

    public function onGetDocument() {
        if($this->user_tender) {
            $bid = Application::where('bid_id', post('bid_id'))->first();
            $api = new Api();
            dd($api->getDocumentFromBid($bid, $this->user_tender,post('doc_url')));
        }
    }

    public function onRun()
    {
        $redirect = $this->redirectTo();

        if($redirect instanceof RedirectResponse)
        {
            return $redirect;
        }

        if(!$this->item) {
            return false;
        }

        //$qs = $this->user_tender->questionsGroup();
        //$bids = $this->user_tender->bidsGroup();

        $this->page['hide_qualifications_buttons'] = $this->hide_qualifications_buttons;
        $this->page['qualification_next_status'] = $this->qualification_next_status;
        $this->page['_qualifications'] = $this->user_tender ? $this->qualifications : [];
        $this->page['session_key_field'] = $this->sessionKey;
        $this->page['user_contract'] = $this->user_contract;
        $this->page['_contract'] = $this->contract;
        $this->page['CurrentLocale'] = $this->getCurrentLocaleWithoutSlash();
        $this->page['tender_questions'] = $this->getQuestions();
        $this->page['award_id'] = $this->user_tender ? Session::get("tender.{$this->user_tender->tender_system_id}.award_id") : null;
        $this->page['show_guarantee'] = $this->setting->checkAccess('guarantee');
        $this->page['show_contracts'] = $this->setting->checkAccess('contracts');
        $this->page['show_criteria'] = $this->setting->checkAccess('criteria');
        if($this->search_type == 'tender') {
            $this->page['access_to_bid'] = ($this->item->procurementMethodType != 'aboveThresholdTS' || ($this->setting->checkAccess('bidTwoStage') && $this->item->procurementMethodType == 'aboveThresholdTS'));
        }
        $this->page['award_bid'] = $this->award_bid;
        $this->page['user_tender'] = $this->user_tender;
        $this->page['is_auth'] = (bool) $this->user;
        $this->page['tender_id'] = isset($this->item->tenderID) ? $this->item->tenderID : $this->property('tenderID');

        if(isset($this->item->__active_award) && $this->item->status != 'complete') {
            if($complaintPeriod = ComplaintPeriod::where('procurement', $this->item->procurementMethodType)->first()) {
                $this->page['complaintPeriod_end'] = Carbon::createFromTimestamp(strtotime($this->item->__active_award->complaintPeriod->endDate))->addDays($complaintPeriod->days);
            } else {
                $this->page['complaintPeriod_end'] = Carbon::createFromTimestamp(strtotime($this->item->__active_award->complaintPeriod->endDate));
            }
        }

        $aps_by_lot_id=[];

        if($this->item && $this->page['tender_id'] && $this->search_type == 'tender') {
            if ($this->user) {
                $applications = Application::getData(['tender_id' => $this->item->id, 'user_id' => $this->user->id, 'test' => $this->user->is_test]);

                $applications_no_lot=$applications->filter(function($app){
                    return !empty($app->lot_id);
                });

                foreach($applications_no_lot as $app) {
                    $aps_by_lot_id[$app->lot_id]=$app;
                }

                if ($this->isMultilot()) {

                    $this->page['tender_app'] = $applications;
                    $lot_q = [];

                    if(count($applications) > 0) {

                        if (empty(post())) {
                            $applications[0]->clearChangeDocuments();
                            //$applications[0]->clearDocuments();
                        }

                        if($applications[0]) {
                            $applications[0]->__documents = $applications[0]->documents->where('user_id', $this->user->id);
                        }

                        foreach ($applications AS $ak => $app) {

                            if($app->lot_id) {
                                $_lot = array_first($this->item->lots, function ($lot, $lkey) use ($app) {
                                    return $lot->id == $app->lot_id;
                                });

                                if ($_lot) {
                                    $app->lot_title = $_lot->title;
                                }
                            }
                            foreach($app->bidDocuments as $biddocument) {
                                $biddocument->_document = array_first($applications[0]->__documents, function($document, $dkey) use($biddocument) {
                                    return $biddocument->system_file_id == $document->id;
                                });
                            }
                        }
                    }

                    if($this->user_tender) {
			if(is_object($this->item->lots)){
				$foreach_lots=$this->item->lots->filter(function($lot){
					return !empty($lot->__questions);
				});
			}else{
				$foreach_lots=array_where($this->item->lots, function($lot){
                                        return !empty($lot->__questions);
                                });
			}

                        foreach ($foreach_lots as $lot) {
                            $answer = 0;

                            foreach ($lot->__questions AS $q) {
                                if (!isset($q->answer)) {
                                    $answer++;
                                }
                            }

                            $lot_q[$lot->id] = $answer;
                        }
                    }

                    $this->page['lot_q'] = $lot_q;

                } else {
                    $application = array_first($applications, function ($app, $k) {
                        return true;
                    });

                    if($application) {
                        $application->__documents = $application->documents->where('user_id', $this->user->id);
                    }

                    if ($application && empty(post())) {
                        $application->clearChangeDocuments();
                        //$application->clearDocuments();
                    }
                    $this->page['tender_app'] = $application;

                    if ($application && $this->item->status == 'active.auction') {
                        $api = new Api($this->gos_tender);

                        if ($r = $api->getBidData($this->page['tender_app'], $this->item)) {

                            if (isset($r->data->participationUrl)) {
                                $this->page['participationUrl'] = $r->data->participationUrl;
                            }
                        }
                    }

		    $applications=$applications->filter(function($app){
			return !empty($app->lot_id);
		    });

                    foreach($applications as $app){
                        $aps_by_lot_id[$app->lot_id]=$app;
                    }
                }

                $this->page['lots_apps'] = $aps_by_lot_id;

                $tariff = Tariff::getTariff(['is_gov' => stripos($this->item->tenderID, 'R-') === FALSE, 'price' => $this->item->value->amount, 'currency' => $this->item->value->currency]);
                $this->page['tariff'] = $tariff;
            }

            $documents = TenderFile::where('tender_id', $this->item->id)->get();

            if (count($documents) && !isset($this->item->__tender_documents)) {
                $this->page['waiting_documents'] = true;
            } else {
                $this->page['waiting_documents'] = false;
            }

            if (isset($this->item->enquiryPeriod->endDate)) {
                $this->page['access_to_questions'] = Carbon::now()->timestamp < strtotime($this->item->enquiryPeriod->endDate);
            } else {
                $this->page['access_to_questions'] = false;
                $this->page['access_to_answers'] = false;
            }

            if($this->page['access_to_questions']) {
                $this->page['access_to_questions'] = !$this->user_tender && $this->user && ($this->item->status == 'active.enquiries' || ($this->item->procurementMethodType == 'aboveThresholdTS' && in_array($this->item->status, ['active.enquiries', 'active.tendering'])));
                $this->page['access_to_answers'] =  $this->user_tender && ($this->item->status == 'active.enquiries' || ($this->item->procurementMethodType == 'aboveThresholdTS' && in_array($this->item->status, ['active.enquiries', 'active.tendering'])));
            }
        }

        if(!empty($this->item->procurementMethodType)){
            $this->page['show_enquire_period_end'] = ($this->item->procurementMethodType == 'aboveThresholdTS' && !in_array($this->item->status, ['active.enquiries', 'active.tendering']) || ($this->item->procurementMethodType != 'aboveThresholdTS' && $this->item->status != 'active.enquiries')) && $this->item->enquiryPeriod->endDate;
        }else{
            $this->page['show_enquire_period_end']=false;
        }


        if($this->search_type == 'tender') {
            $this->messages = MessageTenderPage::instance();

            if($this->messages = $this->messages->get_value('tender_page_messages')) {
                if(is_array($this->messages)) {
                    foreach ($this->messages AS $k => $message) {
                        if ($message['tender_status'] == $this->item->status) {
                            if ($this->user && !$this->user->checkGroup($message['user_type']) && $message['user_type'] != 'all') {
                                unset($this->messages[$k]);
                            } elseif ((!$this->user || !$this->user->checkGroup($message['user_type'])) && $message['user_type'] != 'all') {
                                unset($this->messages[$k]);
                            }
                        } else {
                            unset($this->messages[$k]);
                        }
                    }

                    $this->page['messages'] = $this->messages;
                }
            }
        }
    }

    public function onRender()
    {
        if(!$this->item) {
            return $this->renderPartial('@messages/tender_error');
        }

        $this->addJs('assets/js/tender-validation.js');

        $areas = Area::where('is_enabled', true)->get();

        if($areas) {
            $areas=$areas->each(function ($area){
                $area->url = $area->tender_url ? $this->parseAreaUrl($area->tender_url) : false;
            });
        }

        return $this->renderPartial('@'.$this->search_type, [
            'qualifications_status' => $this->search_type == 'tender' ? Status::getStatuses('qualification') : [],
            'documents_status' => $this->search_type == 'tender' ? Status::getStatuses('document') : [],
            'bids_status' => $this->search_type == 'tender' ? Status::getStatuses('bid') : [],
            'contracts_status' => $this->search_type == 'tender' ? Status::getStatuses('contract') : [],
            'back_url' => starts_with(Request::server('HTTP_REFERER'), env('APP_URL').'/'.$this->search_type.'/search') ? Request::server('HTTP_REFERER') : false,
            'item' => $this->item,
            'error' => $this->error,
            'areas' => $areas,
            'is_multilot' => $this->isMultilot(),
        ]);
    }

    /**
     * @return bool
     */
    private function isMultilot()
    {
        return (isset($this->item->lots)) ? sizeof($this->item->lots) > 1 : false;
    }

    /**
     * @param $url
     * @return mixed
     */
    public function parseAreaUrl($url)
    {
        foreach($this->item as $key=>$value) {
            if(is_string($value)) {
                $url=str_replace('{'.$key.'}', $value, $url);
            }
        }

        return $url;
    }

    public function onDeclineMulti()
    {
        if($this->user) {

            $applications = $this->user->applications()->byNotEmptyBid()->byTender($this->item->id)->byTest($this->user->is_test)->get();
            //Application::getData(['tender_id' => $this->item->id, 'test' => $this->user->is_test, 'user_id' => $this->user->id]);

            if(count($applications) > 0){
                $api=new Api($this->gos_tender);
                $dr = false;

                foreach($applications as $k => $application) {
                    if($k === 0) {
                        $dr = $api->declineSingleLot($application, $this->item);
                    }

                    if ($dr) {
                        foreach ($application->bidDocuments as $document) {
                            DB::statement('DELETE FROM perevorot_rialtotender_application_files WHERE id=' . $document->id);

                            if($file = File::find($document->system_file_id)) {
                                $file->delete();
                            }
                        }

                        $application->user->pay($this->item, Payment::PAYMENT_TYPE_CANCELED_BID, $application);

                        if(!$k) {
                            Event::fire('perevorot.form.bid', [
                                'tender' => $this->item,
                                'user' => $application,
                                'type' => 'declined',
                            ], true);
                        }

                        $application->delete();

                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.applications');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.memory.applications');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.is_application_created');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.step_number');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.lot_step');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.u_applications');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.memory.u_applications');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.updated');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.u_step_number');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.u_lot_step');
                        Session::remove('tender_' . $this->item->tenderID . '_multi_lot.is_application_updated');
                    }
                }

                return [
                    '#application' => $this->renderPartial('@messages/decline_bid')
                ];
            }
        }
    }

    public function onDeclineSingle()
    {
        if($this->user) {

            $application = $this->user->applications()->byNotEmptyBid()->byTender($this->item->id)->byTest($this->user->is_test)->first();
            //$application = Application::getData(['tender_id' => $this->item->id, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => '1']);

            if($application){

                $api=new Api($this->gos_tender);

                if($api->declineSingleLot($application, $this->item)){
                    foreach ($application->bidDocuments as $document) {
                        DB::statement('DELETE FROM perevorot_rialtotender_application_files WHERE id=' . $document->id);

                        if($file = File::find($document->system_file_id)) {
                            $file->delete();
                        }
                    }

                    $application->user->pay($this->item, Payment::PAYMENT_TYPE_CANCELED_BID, $application);

                    Event::fire('perevorot.form.bid', [
                        'tender' => $this->item,
                        'user' => $application,
                        'type' => 'declined',
                    ], true);

                    $application->delete();
                }

                return [
                    '#application' => $this->renderPartial('@messages/decline_bid')
                ];
            }
        }
    }
}
