<?php namespace Perevorot\Users;

use Backend\Facades\Backend;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Classes\AuthManager;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Traits\UserSetting;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Session;
use Redirect;
use Input;
use Schema;
use Request;
use System\Models\MailSetting;

class Plugin extends PluginBase
{
    use CurrentLocale, UserSetting;

    public $elevated = true;
    public $user_mode;

    public $require = [
        'RainLab.User'
    ];

    public function boot()
    {
        if (!env('DB_USERNAME') ||!Schema::hasTable('system_settings')) {
            return true;
        }

        App::register('Perevorot\Users\ValidatorServiceProvider');
        ;
        $mailSetting = MailSetting::instance();

        $notification['tender_updated'] = (bool)@$mailSetting->value['notifi_tender_updated'];
        $notification['tender_created'] = (bool)@$mailSetting->value['notifi_tender_created'];
        $notification['plan_updated'] = (bool)@$mailSetting->value['notifi_plan_updated'];
        $notification['plan_created'] = (bool)@$mailSetting->value['notifi_plan_created'];
        $notification['tender_cancelled'] = (bool)@$mailSetting->value['notifi_tender_cancelled'];
        $notification['tender_cancelled_lot'] = (bool)@$mailSetting->value['notifi_tender_cancelled_lot'];
        $notification['contract'] = (bool)@$mailSetting->value['notifi_contract'];
        $notification['contract_changes'] = (bool)@$mailSetting->value['notifi_contract_changes'];
        $notification['supplier_contract'] = (bool)@$mailSetting->value['notifi_supplier_contract'];
        $notification['supplier_contract_changes'] = (bool)@$mailSetting->value['notifi_supplier_contract_changes'];

        Event::listen('backend.menu.extendItems', function($manager) use($notification) {
            $manager->removeMainMenuItem('Rainlab.User', 'user');

            if (env('APP_ENV') != 'local') {
                $manager->removeMainMenuItem('Rainlab.Builder', 'builder');
            }
        });

        Event::listen('perevorot.users.contract_changes', function($contract) use ($notification) {

            $link = Request::root() . $this->getCurrentLocale($contract->user->lang) . 'contract/' . $contract->cid;
            $this->user_mode = $this->checkUserMode($contract->user);
            $item = json_decode($this->getContract($contract->contract_id));

            if(isset($item->data)) {
                $item = $item->data;
            }

            if($notification['contract_changes']) {
                Mail::send('perevorot.users::' . $this->getLocaleForEmail($contract->user->lang) . 'mail.contract_changes', ['link' => $link], function ($message) use ($contract) {
                    $message->to($contract->user->email, $contract->user->username);
                });
            }

            if($notification['supplier_contract_changes'] && isset($item->suppliers[0]->contactPoint->email)) {
                Mail::send('perevorot.users::' . $this->getLocaleForEmail($contract->user->lang) . 'mail.supplier_contract_changes', ['link' => $link], function ($message) use ($item) {
                    $message->to($item->suppliers[0]->contactPoint->email, $item->suppliers[0]->contactPoint->name);
                });
            }
        });

        Event::listen('perevorot.users.contract', function($contract) use($notification) {

            $link = Request::root() . $this->getCurrentLocale($contract->user->lang) . 'contract/' . $contract->cid;
            $this->user_mode = $this->checkUserMode($contract->user);
            $item = json_decode($this->getContract($contract->contract_id));

            if(isset($item->data)) {
                $item = $item->data;
            }

            if($notification['contract']) {
                Mail::send('perevorot.users::' . $this->getLocaleForEmail($contract->user->lang) . 'mail.contract', ['link' => $link], function ($message) use ($contract) {
                    $message->to($contract->user->email, $contract->user->username);
                });
            }

            if($notification['supplier_contract'] && isset($item->suppliers[0]->contactPoint->email)) {
                Mail::send('perevorot.users::' . $this->getLocaleForEmail($contract->user->lang) . 'mail.supplier_contract', ['link' => $link], function ($message) use ($item) {
                    $message->to($item->suppliers[0]->contactPoint->email, $item->suppliers[0]->contactPoint->name);
                });
            }
        });

        Event::listen('perevorot.users.plan', function($tender, $type) use($notification) {

            if(!$notification['plan_' . $type]) { return; }

            $link = Request::root() . $this->getCurrentLocale($tender->user->lang) . 'plan/' . $tender->plan_id;

            Mail::send('perevorot.users::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.plan_' . $type, ['link' => $link], function ($message) use ($tender) {
                $message->to($tender->user->email, $tender->user->username);
            });
        });

        Event::listen('perevorot.users.tender', function($tender, $type, $lot = false) use($notification) {

            if(!$notification['tender_' . $type. ($lot ? '_lot' : '')]) { return; }

            $link = Request::root() . $this->getCurrentLocale($tender->user->lang) . 'tender/' . $tender->tender_id . ($lot ? ('/lots/'.$lot) : '');

            Mail::send('perevorot.users::' . $this->getLocaleForEmail($tender->user->lang) . 'mail.tender_' . $type . ($lot ? '_lot' : ''), ['link' => $link], function ($message) use ($tender) {
                $message->to($tender->user->email, $tender->user->username);
            });
        });
    }

    private function getContract($id)
    {
        if(empty($id))
            return '';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(env('API_'.$this->user_mode.'LOGIN') && env('API_'.$this->user_mode.'PASSWORD')){
            curl_setopt($ch, CURLOPT_USERPWD, env('API_'.$this->user_mode.'LOGIN') . ":" . env('API_'.$this->user_mode.'PASSWORD'));
        }

        \IntegerLog::info('contract.search.id', $id);

        $path=env('API_'.$this->user_mode.'CONTRACT').'/'.$id;

        if(isset($_GET['api']) && getenv('APP_ENV')=='local')
            dd($path);

        curl_setopt($ch, CURLOPT_URL, $path);

        $result=curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function registerMailTemplates()
    {
        return [
            'perevorot.users::mail.contract_changes' => 'contract changes was activated',
            'perevorot.users::mail.contract' => 'contract updated',
            'perevorot.users::mail.supplier_contract_changes' => 'contract changes was activated for supplier',
            'perevorot.users::mail.supplier_contract' => 'contract updated for supplier',
            'perevorot.users::mail.plan_updated' => 'plan updated',
            'perevorot.users::mail.plan_created' => 'plan created',
            'perevorot.users::mail.tender_updated' => 'tender updated',
            'perevorot.users::mail.tender_created' => 'tender created',
            'perevorot.users::mail.tender_cancelled' => 'tender cancelled',
            'perevorot.users::mail.tender_cancelled_lot' => 'tender cancelled lot',
        ];
    }

    public function register()
    {
        $alias = AliasLoader::getInstance();
        $alias->alias('Auth', Auth::class);

        App::singleton('perevorot.users.auth', function() {
            return AuthManager::instance();
        });
    }

    public function registerComponents()
    {
        return [
            'Perevorot\Users\Components\ContractFiles' => 'ContractFiles',
            'Perevorot\Users\Components\Contracts' => 'Contracts',
            'Perevorot\Users\Components\RegistrationForm' => 'RegistrationForm',
            'Perevorot\Users\Components\Profile' => 'Profile',
            'Perevorot\Users\Components\ResetPassword' => 'ResetPassword',
            'Perevorot\Users\Components\TenderCreate' => 'TenderCreate',
            'Perevorot\Users\Components\PlanCreate' => 'PlanCreate',
            'Perevorot\Users\Components\TenderCancelling' => 'TenderCancelling',
            'Perevorot\Users\Components\TenderProject' => 'TenderProject',
            'Perevorot\Users\Components\PlanProject' => 'PlanProject',
        ];
    }

    public function registerSettings()
    {
        return [
            'externalauth' => [
                'label'       => 'Внешняя авторизация',
                'description' => 'Настройки логина пользователей по внешнему сервису авторизации',
                'category'    => SettingsManager::CATEGORY_USERS,
                'icon'        => 'icon-user',
                'class'       => 'Perevorot\Users\Models\ExternalAuth',
                'order'       => 10,
                'keywords'    => '',
                'permissions' => ['rialtotender.externalauth_permission'],
            ],
            'messages' => [
                'label'       => 'Регистрация',
                'description' => 'Управление текстами при регистрации',
                'category'    => 'Формы',
                'icon'        => 'icon-user-plus',
                'class'       => 'Perevorot\Users\Models\Message',
                'order'       => 10,
                'keywords'    => '',
                'permissions' => ['rialtotender.regtips_permission'],
            ],
            'messages_application' => [
                'label'       => 'Подача предложения',
                'description' => 'Управление текстами при подаче на предложения на тендер',
                'category'    => 'Формы',
                'icon'        => 'icon-gavel',
                'class'       => 'Perevorot\Users\Models\MessageApplication',
                'order'       => 12,
                'keywords'    => 'geography place placement',
                'permissions' => ['rialtotender.bidtips_permission'],
            ],
            'messages_tender' => [
                'label'       => 'Добавление тендера',
                'description' => 'Управление текстами при добавлении нового тендера',
                'category'    => 'Формы',
                'icon'        => 'icon-briefcase',
                'class'       => 'Perevorot\Users\Models\MessageTender',
                'order'       => 13,
                'keywords'    => 'geography place placement',
                'permissions' => ['rialtotender.tendertips_permission'],
            ],
            'messages_plan' => [
                'label'       => 'Добавление плана',
                'description' => 'Управление текстами при добавлении нового плана',
                'category'    => 'Формы',
                'icon'        => 'icon-line-chart',
                'class'       => 'Perevorot\Users\Models\MessagePlan',
                'order'       => 14,
                'keywords'    => 'geography place placement',
                'permissions' => ['rialtotender.plantips_permission'],
            ],
            'messages_tender_page' => [
                'label'       => 'Страница тендера',
                'description' => 'Управление текстами на странице тендера',
                'category'    => 'Формы',
                'icon'        => 'icon-file-text',
                'class'       => 'Perevorot\Users\Models\MessageTenderPage',
                'order'       => 14,
                'keywords'    => 'geography place placement',
                'permissions' => ['rialtotender.tenderpagetips_permission'],
            ],
            'messages_tender_cancel' => [
                'label'       => 'Страница отмены тендера',
                'description' => 'Управление текстами на странице отмены тендера',
                'category'    => 'Формы',
                'icon'        => 'icon-file-text',
                'class'       => 'Perevorot\Users\Models\MessageTenderCancel',
                'order'       => 15,
                'keywords'    => 'geography place placement',
                'permissions' => ['rialtotender.tendercanceltips_permission'],
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'user' => [
                'label'       => 'rainlab.user::lang.users.menu_label',
                'url'         => Backend::URL('perevorot/users/users'),
                'icon'        => 'icon-user',
                'iconSvg'     => 'plugins/rainlab/user/assets/images/user-icon.svg',
                'permissions' => ['rialtotender.users.*'],
                'order'       => 500,
            ]
        ];
    }
}
