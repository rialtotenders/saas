<?php namespace Perevorot\Form;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use System\Classes\PluginBase;
use Illuminate\Support\Facades\App;
use System\Models\MailSetting;
use Schema;

class Plugin extends PluginBase
{
    use CurrentLocale;

    public function boot()
    {
        if (!env('DB_USERNAME') || !Schema::hasTable('system_settings')) {
            return true;
        }

        App::register('Perevorot\Form\ValidatorServiceProvider');

        $mailSetting = MailSetting::instance();
        $notification['declined_bid'] = (bool)@$mailSetting->value['notifi_declined_bid'];
        $notification['updated_bid'] = (bool)@$mailSetting->value['notifi_updated_bid'];
        $notification['created_bid'] = (bool)@$mailSetting->value['notifi_created_bid'];
        $notification['contract_terminated'] = (bool)@$mailSetting->value['notifi_contract_terminated'];
        $notification['supplier_contract_terminated'] = (bool)@$mailSetting->value['notifi_supplier_contract_terminated'];
        $notification['tender_contract_activated'] = (bool)@$mailSetting->value['notifi_tender_contract_activated'];
        $notification['tender_contract_updated'] = (bool)@$mailSetting->value['notifi_tender_contract_updated'];
        $notification['supplier_tender_contract_activated'] = (bool)@$mailSetting->value['notifi_supplier_tender_contract_activated'];
        $notification['supplier_tender_contract_updated'] = (bool)@$mailSetting->value['notifi_supplier_tender_contract_updated'];
        $notification['qualification_chose'] = (bool)@$mailSetting->value['notifi_qualification_chose'];
        $notification['award_chose'] = (bool)@$mailSetting->value['notifi_award_chose'];
        $notification['qualification_chose_supplier'] = (bool)@$mailSetting->value['notifi_qualification_chose_supplier'];
        $notification['award_chose_supplier'] = (bool)@$mailSetting->value['notifi_award_chose_supplier'];
        $notification['answer_created'] = (bool)@$mailSetting->value['notifi_answer_created'];
        $notification['question_created'] = (bool)@$mailSetting->value['notifi_question_created'];

        Event::listen('perevorot.form.bid', function($tender, $bid, $type) use($notification) {

            if(!$notification[$type.'_bid']) { return; }

            $link = Request::root() . $this->getCurrentLocale($bid->user->lang) . 'tender/' . $tender->tenderID;
            $params = [
                'link' => $link,
                'dt' => Carbon::now()->format('H:i d.m.Y'),
                'docs' => $bid->bidDocuments,
            ];

            Mail::send('perevorot.form::' . $this->getLocaleForEmail($bid->user->lang) . 'mail.'.$type.'_bid', $params, function ($message) use ($bid) {
                $message->to($bid->user->email, $bid->user->username);
            });
        });

        Event::listen('perevorot.form.contract_terminated', function($contract, $item) use($notification) {

            $link = Request::root() . $this->getCurrentLocale($contract->user->lang) . 'contract/' . $contract->cid;

            if($notification['contract_terminated']) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($contract->user->lang) . 'mail.contract_terminated', ['link' => $link], function ($message) use ($contract) {
                    $message->to($contract->user->email, $contract->user->username);
                });
            }

            if($notification['supplier_contract_terminated'] && isset($item->suppliers[0]->contactPoint->email)) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($contract->user->lang) . 'mail.supplier_contract_terminated', ['link' => $link], function ($message) use ($item) {
                    $message->to($item->suppliers[0]->contactPoint->email, $item->suppliers[0]->contactPoint->name);
                });
            }
        });

        Event::listen('perevorot.form.tender_contract', function($tender, $type, $contract) use($notification) {

            $link = Request::root() . $this->getCurrentLocale($tender->user->lang) . 'tender/' . $tender->tender_id;

            if($notification['tender_contract_'.$type]) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.tender_contract_' . $type, ['link' => $link], function ($message) use ($tender) {
                    $message->to($tender->user->email, $tender->user->username);
                });
            }

            if($notification['supplier_tender_contract_'.$type] && isset($contract->suppliers[0]->contactPoint->email)) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.supplier_tender_contract_'.$type, ['link' => $link], function ($message) use ($contract) {
                    $message->to($contract->suppliers[0]->contactPoint->email, $contract->suppliers[0]->contactPoint->name);
                });
            }
        });

        Event::listen('perevorot.form.qualification_chose', function($tender, $status, $qualification) use($notification) {

            $link = Request::root() . $this->getCurrentLocale($tender->user->lang) . 'tender/' . $tender->tender_id;

            if($notification['qualification_chose']) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.qualification_set_' . $status, ['link' => $link, 'qualification' => $qualification], function ($message) use ($tender) {
                    $message->to($tender->user->email, $tender->user->username);
                });
            }

            if($notification['qualification_chose_supplier'] && isset($qualification->__tenderers->contactPoint->email)) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.supplier_qualification_set_' . $status, ['link' => $link], function ($message) use ($qualification) {
                    $message->to($qualification->__tenderers->contactPoint->email, $qualification->__tenderers->contactPoint->name);
                });
            }
        });

        Event::listen('perevorot.form.award_chose', function($tender, $status, $award) use($notification) {

            $link = Request::root() . $this->getCurrentLocale($tender->user->lang) . 'tender/' . $tender->tender_id;

            if($notification['award_chose']) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.award_set_' . $status, ['link' => $link, 'award' => $award], function ($message) use ($tender) {
                    $message->to($tender->user->email, $tender->user->username);
                });
            }

            if($notification['award_chose_supplier'] && isset($award->suppliers[0]->contactPoint->email)) {
                Mail::send('perevorot.form::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.supplier_award_set_' . $status, ['link' => $link], function ($message) use ($award) {
                    $message->to($award->suppliers[0]->contactPoint->email, $award->suppliers[0]->contactPoint->name);
                });
            }
        });

        Event::listen('perevorot.form.answer_created', function($question) use($notification) {

            if(!$notification['answer_created']) { return; }

            $tender = $question->tender;
            $user = $question->user;
            $link = Request::root() . $this->getCurrentLocale($user->lang) . 'tender/' . $tender->tender_id . '#questions';

            Mail::send('perevorot.form::' . $this->getLocaleForEmail($user->lang) . 'mail.answer_created', ['link' => $link], function ($message) use ($user) {
                $message->to($user->email, $user->username);
            });
        });

        Event::listen('perevorot.form.question_created', function($question) use($notification) {

            if(!$notification['question_created']) { return; }

            $tender = $question->tender;
            $user = $tender->user;
            $link = Request::root() . $this->getCurrentLocale($user->lang) . 'tender/' . $tender->tender_id . '#questions';

            Mail::send('perevorot.form::' . $this->getLocaleForEmail($user->lang) . 'mail.question_create', ['link' => $link], function ($message) use ($user) {
                $message->to($user->email, $user->username);
            });
        });
    }

    public function registerMailTemplates()
    {
        return [
            'perevorot.form::mail.question_create' => 'when question was created',
            'perevorot.form::mail.answer_created' => 'when answer was created',
            'perevorot.form::mail.award_set_active' => 'when award was set to active',
            'perevorot.form::mail.award_set_cancelled' => 'when award was set to cancelled',
            'perevorot.form::mail.award_set_unsuccessful' => 'when award was set to unsuccessful',
            'perevorot.form::mail.supplier_award_set_active' => 'to supplier was set to active',
            'perevorot.form::mail.supplier_award_set_cancelled' => 'to supplier award was set to cancelled',
            'perevorot.form::mail.supplier_award_set_unsuccessful' => 'to supplier award was set to unsuccessful',
            'perevorot.form::mail.qualification_set_active' => 'when qualification was set to active',
            'perevorot.form::mail.qualification_set_cancelled' => 'when qualification was set to cancelled',
            'perevorot.form::mail.qualification_set_unsuccessful' => 'when qualification was set to unsuccessful',
            'perevorot.form::mail.supplier_qualification_set_active' => 'to supplier qualification was set to active',
            'perevorot.form::mail.supplier_qualification_set_cancelled' => 'to supplier qualification was set to cancelled',
            'perevorot.form::mail.supplier_qualification_set_unsuccessful' => 'to supplier qualification was set to unsuccessful',
            'perevorot.form::mail.contract_terminated' => 'when contract terminated',
            'perevorot.form::mail.supplier_contract_terminated' => 'when contract terminated for supplier',
            'perevorot.form::mail.tender_contract_activated' => 'when contract activated',
            'perevorot.form::mail.tender_contract_updated' => 'when contract updated',
            'perevorot.form::mail.supplier_tender_contract_activated' => 'when contract activated for supplier',
            'perevorot.form::mail.supplier_tender_contract_updated' => 'when contract updated for supplier',
            'perevorot.form::mail.created_bid' => 'created bid',
            'perevorot.form::mail.declined_bid' => 'declined bid',
            'perevorot.form::mail.updated_bid' => 'updated bid',
        ];
    }

    public function registerComponents()
    {
        return [
            'Perevorot\Form\Components\ContractPage' => 'ContractPage',
            'Perevorot\Form\Components\SearchResult' => 'searchresult',
            'Perevorot\Form\Components\SingleDocument' => 'singledocument',
            'Perevorot\Form\Components\ApplicationCreater' => 'ApplicationCreater',
            'Perevorot\Form\Components\ApplicationUpdater' => 'ApplicationUpdater',
            'Perevorot\Form\Components\ApplicationFiles' => 'ApplicationFiles',
            'Perevorot\Form\Components\SingleLot' => 'SingleLot',
        ];
    }

    public function registerSettings()
    {
        return [];
    }
}
