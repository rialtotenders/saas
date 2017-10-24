<?php namespace Perevorot\Form\Components;

use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\Request;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Components\Traits\DataHelpers;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Area;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\ComplaintPeriod;
use Perevorot\Rialtotender\Models\TenderFile;
use Perevorot\Users\Facades\Auth;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\Status;
use Perevorot\Rialtotender\Models\Tariff;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\AccessToTenders;
use Illuminate\Http\RedirectResponse;
use Perevorot\Rialtotender\Models\Setting;
use Illuminate\Support\Facades\Session;
use October\Rain\Support\Facades\Form;

class SingleLot extends ComponentBase
{
    use AccessToTenders, DataHelpers;

    /**
     * @var
     */
    private $item;

    /**
     * @var
     */
    private $lot;
    private $user_tender;
    private $setting;
    private $contract;
    private $user_contract;
    private $sessionKey;
    private $user;
    public $gos_tender;
    public $qualifications;
    public $qualification_next_status;
    public $hide_qualifications_buttons;


    public function componentDetails()
    {
        return [
            'name'        => 'SingleLot Component',
            'description' => 'No description provided yet...'
        ];
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

    public function init()
    {
        $this->sessionKey = Form::sessionKey();
        $this->setting = Setting::instance();
        $this->item = Parser::instance()->tender_parse($this->property('tender_id'));
        $this->user = Auth::getUser();

        if(!$this->item) {
            return false;
        }

        $this->gos_tender = stripos($this->item->tenderID, 'R-') === FALSE;

        if($this->user) {
            $this->user_tender = Tender::getData(['tender_id' => $this->item->tenderID, 'gov' => $this->user->is_gov, 'test' => $this->user->is_test, 'user_id' => $this->user->id, 'limit' => 1]);
        }

        if(!$this->user_tender instanceof Tender) {
            $this->user_tender = null;
        }

        $lot_id = $this->property('lot_id');

        $this->lot = array_first($this->item->lots, function ($item, $key) use ($lot_id) {
            return $item->id == $lot_id;
        });

        if(!$this->lot) {
            return false;
        }

        if($this->user_tender) {
            if ($award_id = Session::get("tender.{$this->user_tender->tender_system_id}.lot.{$this->lot->id}.award_id")) {
                foreach ($this->lot->awards AS $award) {
                    if ($award_id == $award->id && $award->status != Session::get("tender.{$this->user_tender->tender_system_id}.lot.{$this->lot->id}.award_status")) {
                        Session::remove("tender.{$this->user_tender->tender_system_id}.lot.{$this->lot->id}.award_id");
                        Session::remove("tender.{$this->user_tender->tender_system_id}.lot.{$this->lot->id}.award_status");
                    }
                }
            }

            if ($this->user_tender && $this->lot->status == 'active' &&  $this->item->status == 'active.pre-qualification' && !empty($this->lot->__qualifications)) {

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

            if ($this->item->status == 'active.qualification' && !empty($this->lot->awards)) {

                $component = $this->addComponent(
                    'Perevorot\Uploader\Components\FileUploader',
                    'fileUploader_awards',
                    [
                        //'isMultiPage' => true,
                        'maxSize' => @$this->setting->value['max_file_size'],
                        'deferredBinding' => true,
                        'fileTypes' => @$this->setting->value['file_types'],
                    ]
                );

                if (empty(post())) {
                    $this->user_tender->clearAwardDocuments();
                }

                $component->bindModel('awardDocuments', $this->user_tender);
            }

            if($this->lot->status == 'complete') {
                $this->contract = Contract::getData(['lot_id' => $this->lot->id, 'tender_id' => $this->item->id, 'limit' => 1]);

                if(!$this->contract instanceof Contract) {
                    $this->contract = false;
                }
            }

            if(!empty($this->lot->__contracts) && $this->lot->status != 'complete') {

                $this->user_contract = Contract::getPendingContract($this->lot->__contracts);

                if(!$this->user_contract instanceof Contract) {
                    $this->user_contract = new Contract();
                }

                if (empty(post()) && $this->user_contract) {
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

    public function onRun()
    {
        $redirect = $this->redirectTo();

        if ($redirect instanceof RedirectResponse) {
            return $redirect;
        }

        if(!$this->item || !$this->lot) {
            return false;
        }

        if (isset($this->item->enquiryPeriod->endDate)) {
            $this->page['access_to_questions'] = Carbon::now()->timestamp < strtotime($this->item->enquiryPeriod->endDate);
        } else {
            $this->page['access_to_questions'] = false;
            $this->page['access_to_answers'] = false;
        }

        if($this->page['access_to_questions']) {
            $this->page['access_to_questions'] = !$this->user_tender && $this->user && ($this->item->status == 'active.enquiries' || ($this->item->procurementMethodType == 'aboveThresholdTS' && in_array($this->item->status, ['active.enquiries', 'active.tendering'])));
            $this->page['access_to_answers'] = $this->user_tender && ($this->item->status == 'active.enquiries' || ($this->item->procurementMethodType == 'aboveThresholdTS' && in_array($this->item->status, ['active.enquiries', 'active.tendering'])));
        }

        if ($this->user) {
            $tariff = Tariff::getTariff(['is_gov' => stripos($this->item->tenderID, 'R-') === FALSE, 'price' => $this->item->value->amount, 'currency' => $this->item->value->currency]);
            $this->page['tariff'] = $tariff;
            $this->page['user_bid'] = false;

            $application = Application::getData(['lot_id' => $this->lot->id, 'tender_id' => $this->item->id, 'user_id' => $this->user->id, 'test' => $this->user->is_test, 'limit' => 1]);

            if($application instanceof Application) {

                $this->page['tender_app'] = $application;
                $this->page['user_bid'] = true;

                if ($application && empty(post())) {
                    $application->clearChangeDocuments();
                }

                $application->__documents = $application->documents->where('user_id', $this->user->id);

                if ($application && $this->item->status == 'active.auction') {

                    $api = new Api($this->gos_tender);

                    if ($r = $api->getBidData($application, $this->item)) {

                        if(isset($r->data->lotValues)) {
                            $_lot = array_first($r->data->lotValues, function($value, $key) use($application) {
                                return isset($value->relatedLot) && $value->relatedLot == $application->lot_id;
                            });
                        } else {
                            $_lot = false;
                        }

                        $this->page['participationUrl'] = $_lot && isset($_lot->participationUrl) ? $_lot->participationUrl : null;

                    }
                }
            }

            $lot_q = [];

            if($this->user_tender) {
                foreach (array_where($this->item->lots, function ($lot, $k) {
                    return !empty($lot->__questions);
                }) as $lot) {

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
        }

        $documents = TenderFile::where('tender_id', $this->item->id)->get();

        if (count($documents) && !isset($this->item->__tender_documents)) {
            $this->page['waiting_documents'] = true;
        } else {
            $this->page['waiting_documents'] = false;
        }

        $this->page['hide_qualifications_buttons'] = $this->hide_qualifications_buttons;
        $this->page['qualification_next_status'] = $this->qualification_next_status;
        $this->page['_qualifications'] = $this->user_tender ? $this->qualifications : [];
        $this->page['award_id'] = $this->user_tender ? Session::get("tender.{$this->user_tender->tender_system_id}.lot.{$this->lot->id}.award_id") : null;
        $this->page['session_key_field'] = $this->sessionKey;
        $this->page['user_contract'] = $this->user_contract;
        $this->page['_contract'] = $this->contract;
        $this->page['CurrentLocale'] = $this->getCurrentLocaleWithoutSlash();
        $this->page['show_guarantee'] = $this->setting->checkAccess('guarantee');
        $this->page['show_contracts'] = $this->setting->checkAccess('contracts');
        $this->page['show_criteria'] = $this->setting->checkAccess('criteria');
        $this->page['access_to_bid'] = ($this->item->procurementMethodType != 'aboveThresholdTS' || ($this->setting->checkAccess('bidTwoStage') && $this->item->procurementMethodType == 'aboveThresholdTS'));
        $this->page['lot_questions'] = $this->getQuestions('lot');
        $this->page['tender_questions'] = $this->getQuestions();
        $this->page['user_tender'] = $this->user_tender;

        if(isset($this->lot->__active_award) && $this->item->status != 'complete') {
            if($complaintPeriod = ComplaintPeriod::where('procurement', $this->lot->procurementMethodType)->first()) {
                $this->page['complaintPeriod_end'] = Carbon::createFromTimestamp(strtotime($this->lot->__active_award->complaintPeriod->endDate))->addDays($complaintPeriod->days);
            } else {
                $this->page['complaintPeriod_end'] = Carbon::createFromTimestamp(strtotime($this->lot->__active_award->complaintPeriod->endDate));
            }
        }

        $this->page['show_enquire_period_end'] = ($this->item->procurementMethodType == 'aboveThresholdTS' && !in_array($this->item->status, ['active.enquiries', 'active.tendering']) || ($this->item->procurementMethodType != 'aboveThresholdTS' && $this->item->status != 'active.enquiries')) && $this->item->enquiryPeriod->endDate;
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
                //$area->url = $area->tender_url ? $this->parseAreaUrl($area->tender_url) : false;
                $area->url = $area->tender_url ? $area->tender_url : false;
            });
        }

        return $this->renderPartial('@tender.htm', [
            'show_guarantee' => $this->setting->checkAccess('guarantee'),
            'show_criteria' => $this->setting->checkAccess('criteria'),
            'item' => $this->item,
            //'error' => $this->error,
            'tender_id' => $this->property('tender_id'),
            'qualifications_status' => Status::getStatuses('qualification'),
            'documents_status' => Status::getStatuses('document'),
            'bids_status' => Status::getStatuses('bid'),
            'lot_status' => Status::getStatuses('lot_status'),
            'contracts_status' =>  Status::getStatuses('contract'),
            'areas' => $areas,
            'is_multilot' => (isset($this->item->lots)) ? sizeof($this->item->lots) > 1 : false,
            'lot' => $this->lot,
        ]);
    }
}
