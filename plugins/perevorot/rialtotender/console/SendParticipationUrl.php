<?php namespace Perevorot\Rialtotender\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Mail;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Models\User;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Config;
use Perevorot\Rialtotender\Classes\SetConfig;
use System\Models\MailSetting;

class SendParticipationUrl extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'integer:send_participation_url';

    /**
     * @var string The console command description.
     */
    protected $description = 'Отправка participation url';
    protected $defLocale;
	protected $env;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->send_email();
    }

	private function send_email()
	{
        $config = new SetConfig();
        $env = $config->setConnection($this->option('theme'));
        $mailSetting = DB::table('system_settings')->where('item', 'system_mail_settings')->first();
        $conf = (object)Config::get('mail')['from'];
        $this->defLocale = DB::table('rainlab_translate_locales')
            ->where('is_default', true)
            ->value('code');

        if($mailSetting->value) {
            $mailSetting->value = (array)json_decode($mailSetting->value);
        }

        if(!@$mailSetting->value['notifi_participation_url']) {
            \IntegerLog::info('integer:send_participation_url', 'Send participation url is disable.');
            return false;
        }

        $applications = Application::getData(['is_url_send' => 0]);
        $parser = Parser::instance();
        $_tenders = [];
        $sent = 0;

        foreach($applications AS $app)
        {
            if($app->tender_id)
            {
                if(!isset($_tenders[$app->tender_id])) {

                    if($item = $parser->tender_parse($app->tender_id, 'id', $app->is_test, (array)$env)) {
                        $_tenders[$item->id] = $item;
                    }
                }

                if(!isset($_tenders[$app->tender_id])) {
                    continue;
                }

                if ($_tenders[$app->tender_id]->status == 'active.auction') {
                    $gos_tender = stripos($_tenders[$app->tender_id]->tenderID, 'R-') === FALSE;
                    $api = new Api($gos_tender, (array)$env);

                    if ($r = $api->getBidData($app, $_tenders[$app->tender_id])) {

                        $_data = [];

                        if(isset($r->data->lotValues) && isset($app->lot_id)) {
                            $_data = array_where($r->data->lotValues, function($value, $key) use($app) {
                                return (isset($value->relatedLot) && isset($value->participationUrl)) && $value->relatedLot == $app->lot_id;
                            });
                        } elseif(isset($r->data->participationUrl)) {
                            $_data[] = $r->data;
                        } else {
                            continue;
                        }

                        $user = $app->user;

                        if($user instanceof User) {

                            $params = [
                                'items' => $_data,
                                'dt' => null,
                            ];

                            if ($_tenders[$app->tender_id]->__isMultiLot && isset($_tenders[$app->tender_id]->__lotAuctionPeriod->startDate)) {
                                $params['dt'] = Carbon::createFromTimestamp(strtotime($_tenders[$app->tender_id]->__lotAuctionPeriod->startDate))->format('H:i d.m.Y');
                            }
                            elseif (!$_tenders[$app->tender_id]->__isMultiLot && isset($_tenders[$app->tender_id]->auctionPeriod->startDate)) {
                                $params['dt'] = Carbon::createFromTimestamp(strtotime($_tenders[$app->tender_id]->auctionPeriod->startDate))->format('H:i d.m.Y');
                            }

                            $r = Mail::send('perevorot.rialtotender::'.$this->getLocaleForEmail($user->lang).'mail.send_participation_url', $params, function ($message) use ($user, $conf) {
                                $message->to($user->email, $user->username);
                                $message->from($conf->address, $conf->name);
                            });

                            if ($r) {
                                $app->is_url_send = 1;
                                $app->save();

                                $sent++;

                                \IntegerLog::info('integer:send_participation_url', ('Email to #'.$user->username.' was sent: '.json_encode($_data)));
                            } else {
                                \IntegerLog::info('integer:send_participation_url', ('Email sent error: '.json_encode($_data)));
                            }
                        }
                    }
                } elseif (in_array($_tenders[$app->tender_id]->status, ['complete', 'cancelled', 'unsuccessful'])) {
                    $app->is_url_send = 1;
                    $app->save();
                }
            }
        }

        \IntegerLog::info('integer:send_participation_url', 'Were sent emails: '.$sent);
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
