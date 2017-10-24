<?php namespace Perevorot\Rialtotender\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Integer;
use Perevorot\Rialtotender\Models\Status;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Config;
use Perevorot\Rialtotender\Classes\SetConfig;
use Perevorot\Rialtotender\Models\Application;
use System\Models\MailSetting;
use System\Models\MailTemplate;

class UpdateTenders extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:update_tenders';

    /**
     * @var string The console command description.
     */
    protected $description = 'Tenders update.';
    protected $defLocale;
	protected $env;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->update_tender();
        \IntegerLog::info('integer:update_tenders', 'Tenders update.');
    }

	private function update_tender()
	{
        $config = new SetConfig();
        $this->env = $config->setConnection($this->option('theme'));
        $parser = Parser::instance();
        $tenders = Tender::getData(['complete' => 1, 'without_closed' => true]);
        $mailSetting = DB::table('system_settings')->where('item', 'system_mail_settings')->first();
        $site = $this->env->APP_URL;
        $conf = (object)Config::get('mail')['from'];
        $this->defLocale = DB::table('rainlab_translate_locales')
            ->where('is_default', true)
            ->value('code');

        if($mailSetting->value) {
            $mailSetting->value = (array)json_decode($mailSetting->value);
        }

        $notification['new_questions'] = (bool)@$mailSetting->value['notifi_new_questions'];
        $notification['empty_questions'] = (bool)@$mailSetting->value['notifi_empty_questions'];
        $notification['tender_updated'] = (bool)@$mailSetting->value['notifi_tenders_updated'];
        $notification['award_win'] = (bool)@$mailSetting->value['notifi_award_win'];
        $notification['supplier_award_win'] = (bool)@$mailSetting->value['notifi_supplier_award_win'];

        \IntegerLog::info('integer:update_tenders', 'count of tenders - '.count($tenders));

        foreach($tenders AS $k => $tender)
        {
            $what_update = [];

            if($tender->tender_id && $item = $parser->tender_parse($tender->tender_id, 'tid', $tender->is_test, (array)$this->env)) {

                if($item->__active_award && !$tender->award_notify) {
                    $bid = Application::where('bid_id', $item->__active_award->bid_id)->byTender($tender->tender_system_id)->first();

                    if($bid instanceof Application && $bid->user) {
                        $tender->award_notify = 1;
                        $link = $site . $this->getCurrentLocale($bid->user->lang) . 'tender/' . $tender->tender_id;

                        if($notification['supplier_award_win']) {
                            foreach($item->awards AS $award) {
                                if($award->bid_id != $item->__active_award->bid_id && isset($award->suppliers[0]->contactPoint->email)) {
                                    try {
                                        Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($bid->user->lang) . 'mail.supplier_award_win', ['link' => $link, 'award' => $item->__active_award], function ($message) use ($award, $conf) {
                                            $message->to($award->suppliers[0]->contactPoint->email, $award->suppliers[0]->contactPoint->name);
                                            $message->from($conf->address, $conf->name);
                                        });
                                    }
                                    catch (\Exception $e) {
                                        \IntegerLog::info('integer:update_tenders', 'error: '.$e->getMessage() . ' data: lang-email=' . $this->getLocaleForEmail($tender->user->lang) . '; user-lang=' . $tender->user->lang );
                                    }
                                }
                            }
                        }

                        if($notification['award_win']) {
                            try {
                                Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($bid->user->lang) . 'mail.award_win', ['link' => $link], function ($message) use ($bid, $conf) {
                                    $message->to($bid->user->email, $bid->user->username);
                                    $message->from($conf->address, $conf->name);
                                });
                            }
                            catch (\Exception $e) {
                                \IntegerLog::info('integer:update_tenders', 'error: '.$e->getMessage() . ' data: lang-email=' . $this->getLocaleForEmail($tender->user->lang) . '; user-lang=' . $tender->user->lang );
                            }
                        }
                    }
                }

                if($tender->status != $item->status) {
                    $what_update['изменился статус - '] = "из {$tender->status} на {$item->status};";
                    $tender->status = $item->status;

                    if($notification['tender_updated']) {
                        $tender_link = $site . $this->getCurrentLocale($tender->user->lang) . 'tender/' . $tender->tender_id;
                        $statuses = Status::getStatuses('tender', false, $tender->user->lang);
                        $status = isset($statuses[$tender->status]) ? $statuses[$tender->status] : $tender->status;
                        $qs = $tender->questionsGroup();
                        $bids = $tender->bidsGroup($qs->lists('user_id'));

                        try {
                            Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.tender_status_changed_2', ['link' => $tender_link, 'status' => $status], function ($message) use ($tender, $conf) {
                                $message->to($tender->user->email, $tender->user->username);
                                $message->from($conf->address, $conf->name);
                            });

                            if (!$qs->isEmpty()) {
                                foreach ($qs AS $q) {
                                    Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($q->user->lang) . 'mail.tender_status_changed', ['link' => $tender_link, 'status' => $status], function ($message) use ($q, $conf) {
                                        $message->to($q->user->email, $q->user->username);
                                        $message->from($conf->address, $conf->name);
                                    });
                                }
                            }
                            if (!$bids->isEmpty()) {
                                foreach ($bids AS $q) {
                                    Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($q->user->lang) . 'mail.tender_status_changed', ['link' => $tender_link, 'status' => $status], function ($message) use ($q, $conf) {
                                        $message->to($q->user->email, $q->user->username);
                                        $message->from($conf->address, $conf->name);
                                    });
                                }
                            }
                        }
                        catch (\Exception $e) {
                            \IntegerLog::info('integer:update_tenders', 'error: '.$e->getMessage() . ' data: lang-email=' . $this->getLocaleForEmail($tender->user->lang) . '; user-lang=' . $tender->user->lang );
                        }
                    }
                }

                if($item->status == 'active.enquiries' && isset($item->enquiryPeriod->endDate)) {
                    $tender->date = Carbon::createFromTimestamp(strtotime($item->enquiryPeriod->endDate));
                    $tender->q_end_date = $tender->date;
                    $what_update['изменилась дата статуса - '] = "для {$tender->status} на {$item->enquiryPeriod->endDate};";
                } elseif($item->status == 'active.tendering' && isset($item->tenderPeriod->endDate)) {
                    $tender->date = Carbon::createFromTimestamp(strtotime($item->tenderPeriod->endDate));
                    $what_update['изменилась дата статуса - '] = "для {$tender->status} на {$item->tenderPeriod->endDate};";
                } elseif($item->status == 'active.auction') {

                    if($item->__isMultiLot && isset($item->__lotAuctionPeriod->startDate)) {
                        $tender->date = Carbon::createFromTimestamp(strtotime($item->__lotAuctionPeriod->startDate));
                        $what_update['изменилась дата статуса для мультилота - '] = "для {$tender->status} на {$item->__lotAuctionPeriod->startDate};";
                    } elseif(isset($item->auctionPeriod->startDate)) {
                        $tender->date = Carbon::createFromTimestamp(strtotime($item->auctionPeriod->startDate));
                        $what_update['изменилась дата статуса - '] = "для {$tender->status} на {$item->auctionPeriod->startDate};";
                    } else {
                        $tender->date = null;
                    }

                } else {
                    $tender->date = null;
                }

                if(isset($item->questions)) {

                    $answer = 0;
                    $new_other = false;
                    $other_qs = json_decode($tender->other_qs);

                    foreach($item->questions AS $q) {
                        if(!isset($q->answer)) {
                            $answer++;
                            $local = array_first($tender->questions, function($tq, $tqk) use($q) { return $tq->qid == $q->id; });

                            if(!$local && $other_qs && !in_array($q->id, $other_qs)) {
                                $other_qs[] = $q->id;
                                $new_other = true;
                            } elseif(!$local && !$other_qs) {
                                $other_qs[] = $q->id;
                                $new_other = true;
                            }
                        }
                    }

                    if($tender->empty_questions != $answer) {
                        $what_update['изменился показатель не отвеченных вопросов - '] = "из {$tender->empty_questions} на {$answer};";
                    }

                    $tender->other_qs = json_encode($other_qs);
                    $tender->empty_questions = $answer;

                    if($new_other && $notification['new_questions']) {
                        $tender_link = $site . $this->getCurrentLocale($tender->user->lang) . 'tender/' . $tender->tender_id . '#questions';

                        try {
                            Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.new_questions', ['link' => $tender_link], function ($message) use ($tender, $conf) {
                                $message->to($tender->user->email, $tender->user->username);
                                $message->from($conf->address, $conf->name);
                            });
                        }
                        catch (\Exception $e) {
                            \IntegerLog::info('integer:update_tenders', 'error: '.$e->getMessage() . ' data: lang-email=' . $this->getLocaleForEmail($tender->user->lang) . '; user-lang=' . $tender->user->lang );
                        }
                    }

                    if($item->status == 'active.enquiries' && $item->__isOpenedQuestions && $notification['empty_questions'] && (!$tender->dt_notify_empty_question || Carbon::createFromTimestamp(strtotime($tender->dt_notify_empty_question))->diffInDays(Carbon::now()) > 0)) {

                        $tender_link = $site . $this->getCurrentLocale($tender->user->lang) . 'tender/' . $tender->tender_id . '#questions';
                        $tender->dt_notify_empty_question = Carbon::now();

                        try {
                            Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.empty_questions', ['link' => $tender_link], function ($message) use ($conf, $tender) {
                                $message->to($tender->user->email, $tender->user->username);
                                $message->from($conf->address, $conf->name);
                            });
                        }
                        catch (\Exception $e) {
                            \IntegerLog::info('integer:update_tenders', 'error: '.$e->getMessage() . ' data: lang-email=' . $this->getLocaleForEmail($tender->user->lang) . '; user-lang=' . $tender->user->lang );
                        }
                    }
                }

                if(in_array($tender->status, ['complete', 'cancelled', 'unsuccessful'])) {
                    $tender->is_closed = 1;
                }

                $msg = "success - {$tender->tender_id}.";

                if(!empty($what_update)) {

                    $msg .= ' data: ';

                    foreach($what_update as $_key => $_value) {
                        $msg .= $_key . $_value . ' ';
                    }

                    $msg .= '.';
                }

                \IntegerLog::info('integer:update_tenders', $msg);
            }
            else {
                $tender->status = null;
                $tender->date = null;
                $tender->empty_questions = 0;

                \IntegerLog::info('integer:update_tenders', 'fail - '.$tender->tender_id);
            }

            $tender->save();
        }
    }



    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['theme', null, InputOption::VALUE_OPTIONAL, 'An example option.', null]
        ];
    }

    protected function getCurrentLocale($lang = null)
    {
        $activeLocale = $lang ? $lang : $this->defLocale;
        return $this->defLocale == $activeLocale ? '/' : '/'.$activeLocale.'/';
    }

    protected function getLocaleForEmail($lang = null)
    {
        $activeLocale = $lang ? $lang : $this->defLocale;
        return $this->defLocale == $activeLocale ? '' : ($activeLocale . "::");
    }
}
