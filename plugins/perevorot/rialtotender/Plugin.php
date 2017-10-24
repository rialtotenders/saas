<?php namespace Perevorot\Rialtotender;

use Backend\Models\User;
use Doctrine\DBAL\Driver\Mysqli\MysqliException;
use Illuminate\Support\Facades\Mail;
use October\Rain\Database\Model;
use Perevorot\Rialtotender\Classes\ValidationMessages;
use Perevorot\Rialtotender\Console\AddEmailTpl;
use Perevorot\Rialtotender\Console\DbUpdate;
use Perevorot\Rialtotender\Console\IntegerUpdate;
use Perevorot\Rialtotender\Console\RemoveEmailTpl;
use Perevorot\Rialtotender\Console\RemoveFiles;
use Perevorot\Rialtotender\Console\RemoveFilesWOUser;
use Perevorot\Rialtotender\Console\RemoveTenderFiles;
use Perevorot\Rialtotender\Console\SendParticipationUrl;
use Perevorot\Rialtotender\Console\TruncateData;
use Perevorot\Rialtotender\Console\UpdateTenders;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Integer;
use Perevorot\Rialtotender\Models\Status;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use System\Classes\SettingsManager;
use Perevorot\Users\Models\Message;
use System\Classes\PluginBase;
use Perevorot\Rialtotender\Console\InitialData;
use Perevorot\Rialtotender\Console\MessagesExport;
use Illuminate\Support\Facades\Request;
use App;
use Event;
use Backend;
use Exception;
use Schema;
use October\Rain\Exception\AjaxException;
use October\Rain\Exception\ApplicationException;
use System\Models\MailSetting;
use System\Models\MailTemplate;

class Plugin extends PluginBase
{
    use CurrentLocale;

    public $require = [
        'RainLab.User'
    ];

    public function registerReportWidgets()
    {
        return [
            'Perevorot\Rialtotender\Widgets\GitReport' => [
                'label'   => 'Git отчёт',
                'context' => 'dashboard'
            ]
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'stripslashes' => 'stripslashes',
            ],
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'perevorot.rialtotender::mail.created_account' => 'An account has been created for you',
            'perevorot.rialtotender::mail.created_account_for_admin' => 'New user registration',
            'perevorot.rialtotender::mail.send_participation_url' => 'Send participation url',
            'perevorot.rialtotender::mail.send_if_update_bid' => 'Your bids was updated',
            'perevorot.rialtotender::mail.tender_updated' => 'tender edited for supplier',
            'perevorot.rialtotender::mail.tender_cancelled' => 'tender cancelled for supplier',
            'perevorot.rialtotender::mail.tender_cancelled_lot' => 'tender cancelled lot for supplier',
            'perevorot.rialtotender::mail.empty_questions' => 'isset empty questions',
            'perevorot.rialtotender::mail.new_questions' => 'isset new questions',
            'perevorot.rialtotender::mail.award_win' => 'award win',
            'perevorot.rialtotender::mail.supplier_award_win' => 'award win for others',
            'perevorot.rialtotender::mail.tender_status_changed' => 'tender status changed for supplier',
            'perevorot.rialtotender::mail.tender_status_changed_2' => 'tender status changed',
        ];
    }

    public function registerSettings()
    {
        return [
            'messagesexport' => [
                'label'       => 'Обмен сообщениями',
                'description' => 'Экспорт/импорт переводов на страницах',
                'category'    => 'rainlab.translate::lang.plugin.name',
                'icon'        => 'icon-list',
                'url'         => Backend::url('perevorot/rialtotender/messagesexport'),
                'order'       => 1000,
                'keywords'    => '',
                'permissions' => ['rialtotender.messagesexport_permission'],
            ],
            'procurements' => [
                'label'       => 'Типы',
                'description' => 'Типы тендеров, процедур, причин, документов',
                'category'    => 'Rialtotenders',
                'icon'        => 'icon-globe',
                'class'       => 'Perevorot\Rialtotender\Models\Procurement',
                'order'       => 27,
                'keywords'    => '',
                'permissions' => ['rialtotender.procurements_permission'],
            ],
            'functions' => [
                'label'       => 'Общие параметры',
                'description' => 'Настройки работы сайта',
                'category'    => 'Настройки',
                'icon'        => 'icon-check',
                'class'       => 'Perevorot\Rialtotender\Models\Setting',
                'order'       => 0,
                'keywords'    => 'настройки',
                'permissions' => ['rialtotender.settings_permission'],
            ],
            'categories' => [
                'label'       => 'Категории поиска',
                'description' => 'Список категорий для общей фильтрации результатов поиска',
                'category'    => 'Настройки',
                'icon'        => 'icon-filter',
                'url'         => Backend::url('perevorot/rialtotender/categories'),
                'order'       => 25,
                'keywords'    => 'категория',
                'permissions' => ['rialtotender.categories_permission'],
            ],
            'envsettings' => [
                'label'       => 'ENV-настройки',
                'description' => 'Список параметров',
                'category'    => 'Настройки',
                'icon'        => 'icon-filter',
                'class'       => 'Perevorot\Rialtotender\Models\EnvSettings',
                'order'       => 26,
                'keywords'    => 'env настройки',
                'permissions' => ['rialtotender.envsettings_permission'],
            ],
            'statuses' => [
                'label'       => 'Статусы',
                'description' => 'Управление статусами тендера, документов, предложений, квалификаций',
                'category'    => 'Настройки',
                'icon'        => 'icon-tasks',
                'class'       => 'Perevorot\Rialtotender\Models\Status',
                'order'       => 20,
                'keywords'    => 'status статус',
                'permissions' => ['rialtotender.statuses_permission'],
            ],
            'classifiers' => [
                'label'       => 'Класификаторы',
                'description' => 'Управление класификаторами CPV, единиц измерения',
                'category'    => 'Настройки',
                'icon'        => 'icon-list',
                'order'       => 24,
                'keywords'    => 'cpv',
                'class'       => 'Perevorot\Rialtotender\Models\Classifier',
                'permissions' => ['rialtotender.classifiers_permission'],
                'url'         => Backend::url('perevorot/rialtotender/classifiers'),
            ],
            'apilogs' => [
                'label'       => 'Журнал API',
                'description' => 'Учет обращений к ЦБД',
                'category'    => SettingsManager::CATEGORY_LOGS,
                'icon'        => 'icon-database',
                'order'       => 1000,
                'keywords'    => 'api',
                'permissions' => ['rialtotender.apilog_permission'],
                'url'         => Backend::url('perevorot/rialtotender/apilogs'),
            ],
            'tariffs' => [
                'label'       => 'Тарифы',
                'description' => 'Настройка тарифов при добавлении предложения',
                'category'    => 'Финансы',
                'icon'        => 'icon-money',
                'order'       => 10,
                'keywords'    => '',
                'permissions' => ['rialtotender.tariffs_permission'],
                'url'         => Backend::url('perevorot/rialtotender/tariffs'),
            ],
            'currencies' => [
                'label'       => 'Валюты',
                'description' => 'Управление валютами',
                'category'    => 'Финансы',
                'icon'        => 'icon-eur',
                'order'       => 20,
                'keywords'    => '',
                'permissions' => ['rialtotender.currencies_permission'],
                'url'         => Backend::url('perevorot/rialtotender/currencies'),
            ],
            'timingcontrols' => [
                'label'       => 'Контроль сроков',
                'description' => '',
                'category'    => 'Сроки',
                'icon'        => 'icon-list',
                'order'       => 28,
                'keywords'    => '',
                'permissions' => ['rialtotender.timing_controls_permission'],
                'url'         => Backend::url('perevorot/rialtotender/timingcontrols'),
            ],
            'complaintperiods' => [
                'label'       => 'Период обжалования',
                'description' => '',
                'category'    => 'Сроки',
                'icon'        => 'icon-list',
                'order'       => 29,
                'keywords'    => '',
                'permissions' => ['rialtotender.complaintPeriod_permission'],
                'url'         => Backend::url('perevorot/rialtotender/complaintperiods'),
            ],
        ];
    }

    public function boot()
    {
        if (!env('DB_USERNAME') || !Schema::hasTable('system_settings')) {
            return true;
        }

        $mailSetting = MailSetting::instance();

        $notification['tender_updated'] = (bool)@$mailSetting->value['notifi_tender_edited'];
        $notification['tender_cancelled'] = (bool)@$mailSetting->value['notifi_tender_was_cancelled'];
        $notification['tender_cancelled_lot'] = (bool)@$mailSetting->value['notifi_tender_was_cancelled_lot'];

        Event::listen('perevorot.rialtotender.tender_changed', function($tender, $type, $lot = false) use ($notification) {

            if(!$notification['tender_'.$type. ($lot ? '_lot' : '')]) { return; }

            $qs = $tender->questionsGroup();
            $bids = $tender->bidsGroup($qs->lists('user_id'));

            if(!$qs->isEmpty()) {
                foreach($qs AS $q) {
                    $link = Request::root() . $this->getCurrentLocale($q->user->lang) . 'tender/' . $tender->tender_id . ($lot ? ('/lots/'.$lot) : '');

                    Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($q->user->lang) . 'mail.tender_'.$type.($lot ? '_lot' : ''), ['link' => $link], function ($message) use ($q) {
                        $message->to($q->user->email, $q->user->username);
                    });
                }
            }
            if(!$bids->isEmpty()) {
                foreach($bids AS $q) {
                    $link = Request::root() . $this->getCurrentLocale($q->user->lang) . 'tender/' . $tender->tender_id. ($lot ? ('/lots/'.$lot) : '');

                    Mail::send('perevorot.rialtotender::' . $this->getLocaleForEmail($q->user->lang) . 'mail.tender_'.$type, ['link' => $link], function ($message) use ($q) {
                        $message->to($q->user->email, $q->user->username);
                    });
                }
            }

        });

        /*
        $mailSetting = MailSetting::instance();
        $notification['new_questions'] = (bool)@$mailSetting->value['notifi_new_questions'];
        $notification['empty_questions'] = (bool)@$mailSetting->value['notifi_empty_questions'];
        $notification['tender_updated'] = (bool)@$mailSetting->value['notifi_tenders_updated'];
        $notification['award_win'] = (bool)@$mailSetting->value['notifi_award_win'];

        Event::listen('perevorot.rialtotender.award_win', function($tender, $bid, $site, $lang_code, $email_lang_code) use ($notification) {

            if(!$notification['award_win']) { return; }

            $link = $site . $lang_code . 'tender/' . $tender->tender_id;

            if($bid) {
                Mail::send('perevorot.rialtotender::'.$email_lang_code.'mail.award_win', ['link' => $link], function ($message) use ($bid) {
                    $message->to($bid->user->email, $bid->user->username);
                });
            }
        });

        Event::listen('perevorot.rialtotender.new_questions', function($tender, $site, $lang_code, $email_lang_code) use($notification) {

            if(!$notification['new_questions']) { return; }

            $tender_link = $site . $lang_code . 'tender/' . $tender->tender_id . '#questions';

            Mail::send('perevorot.rialtotender::'.$email_lang_code.'mail.new_questions', ['link' => $tender_link], function ($message) use ($tender) {
                $message->to($tender->user->email, $tender->user->username);
            });
        });

        Event::listen('perevorot.rialtotender.empty_questions', function($tender, $site, $lang_code, $email_lang_code) use($notification) {

            if(!$notification['empty_questions']) { return; }

            $tender_link = $site . $lang_code . 'tender/' . $tender->tender_id . '#questions';

            Mail::send('perevorot.rialtotender::'.$email_lang_code.'mail.empty_questions', ['link' => $tender_link], function ($message) use ($tender) {
                $message->to($tender->user->email, $tender->user->username);
            });
        });

        Event::listen('perevorot.rialtotender.tender_updated', function($tender, $site, $lang_code, $email_lang_code) use($notification) {

            if(!$notification['tender_updated']) { return; }

            $tender_link = $site . $lang_code . 'tender/' . $tender->tender_id;
            $statuses = Status::getStatuses('tender', false, $tender->user->lang);
            $status = isset($statuses[$tender->status]) ? $statuses[$tender->status] : $tender->status;

            Mail::send('perevorot.rialtotender::'.$email_lang_code.'mail.tender_updated', ['link' => $tender_link, 'status' => $status], function ($message) use ($tender) {
                $message->to($tender->user->email, $tender->user->username);
            });
        });
        */

        // Extend all backend form usage
        Event::listen('backend.form.extendFields', function($widget) {

            if (!$widget->model instanceof MailSetting) {
                return;
            }

            $widget->addTabFields([
                'notifi_section1' => [
                    'label'   => 'Почтовые уведомления',
                    'comment' => 'Включение или выключение уведомлений о тендера, ставках, аукционах, контрактах',
                    'type'    => 'section',
                    'span' =>'full',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_section2' => [
                    'label'   => 'Почтовые уведомления',
                    'comment' => 'Включение или выключение уведомлений о тендера, ставках, аукционах, контрактах',
                    'type'    => 'section',
                    'span' =>'full',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_participation_url' => [
                    'label'   => 'Отправка participation url',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_new_questions' => [
                    'label'   => 'Новые вопросы',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_empty_questions' => [
                    'label'   => 'Не отвеченные вопросы',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_tender_status_changed_customer' => [
                    'label'   => 'Изменения статуса тендера',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_plan_created' => [
                    'label'   => 'Создание плана',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_plan_updated' => [
                    'label'   => 'Редактирование плана',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_tender_created' => [
                    'label'   => 'Создание тендера',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_tender_updated' => [
                    'label'   => 'Редактирование тендера',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_tender_edited' => [
                    'label'   => 'Обновление тендера',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_tender_cancelled' => [
                    'label'   => 'Отмена тендера',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_tender_cancelled_lot' => [
                    'label'   => 'Отмена лота',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_tender_was_cancelled' => [
                    'label'   => 'Отмена тендера',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_tender_was_cancelled_lot' => [
                    'label'   => 'Отмена лота',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_tender_status_changed_supplier' => [
                    'label'   => 'Изменение статуса тендера',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_declined_bid' => [
                    'label'   => 'Отмена ставки',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_updated_bid' => [
                    'label'   => 'Обновление ставки',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_created_bid' => [
                    'label'   => 'Создание ставки',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_contract_terminated' => [
                    'label'   => 'Активация контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_supplier_contract_terminated' => [
                    'label'   => 'Активация контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_tender_contract_activated' => [
                    'label'   => 'Активация тендерного контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_tender_contract_updated' => [
                    'label'   => 'Обновление тендерного контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_supplier_tender_contract_activated' => [
                    'label'   => 'Активация тендерного контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_supplier_tender_contract_updated' => [
                    'label'   => 'Обновление тендерного контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_qualification_chose' => [
                    'label'   => 'Выбор в переквалификации',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_award_chose' => [
                    'label'   => 'Выбор в квалификации',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_qualification_chose_supplier' => [
                    'label'   => 'Выбор в переквалификации',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_award_chose_supplier' => [
                    'label'   => 'Выбор в квалификации',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_answer_created' => [
                    'label'   => 'Новый ответ',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_question_created' => [
                    'label'   => 'Новый вопрос',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_award_win' => [
                    'label'   => 'Победитель на аукционе',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_supplier_award_win' => [
                    'label'   => 'Победитель на аукционе(для остальных)',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_contract_changes' => [
                    'label'   => 'Активация изменений по контракту',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_contract' => [
                    'label'   => 'Обновления контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления заказчика',
                ],
                'notifi_supplier_contract_changes' => [
                    'label'   => 'Активация изменений по контракту',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
                'notifi_supplier_contract' => [
                    'label'   => 'Обновления контракта',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'Уведомления поставщика',
                ],
            ]);
        });

        // Extend all backend form usage
        Event::listen('backend.form.extendFields', function($widget) {

            // Only for the User model
            if (!$widget->model instanceof \RainLab\User\Models\Settings) {
                return;
            }

            $mail_tpls = MailTemplate::listAllTemplates();

            foreach($mail_tpls AS $k => $tpl) {
                if(count(explode('::', $k)) > 2) {
                    unset($mail_tpls[$k]);
                }
            }

            // Add an extra birthday field
            $widget->addTabFields([
                'user_in_test' => [
                    'label'   => 'По умолчанию тестовый режим',
                    'comment' => 'Все новые пользователи добавляются с активированным по умолчанию тестовым режимом',
                    'type'    => 'switch',
                    'span' =>'left',
                    'tab' => 'rainlab.user::lang.settings.registration_tab',
                ],
                'admin_email' => [
                    'label'   => 'E-mail администратора',
                    'comment' => 'Электронный адрес проверяющего администратора',
                    'type'    => 'text',
                    'span' =>'left',
                    'tab' => 'rainlab.user::lang.settings.activation_tab',
                ],
                'notify_template' => [
                    'label'   => 'Шаблон письма пользователю',
                    'comment' => 'Шаблон письма пользователю после регистрации',
                    'type'    => 'dropdown',
                    'span' =>'left',
                    'tab' => 'rainlab.user::lang.settings.activation_tab',
                    'options' => $mail_tpls
                ],
                'admin_notify_template' => [
                    'label'   => 'Шаблон письма админу',
                    'comment' => 'Шаблон письма админу после новой регистрации',
                    'type'    => 'dropdown',
                    'span' =>'left',
                    'tab' => 'rainlab.user::lang.settings.activation_tab',
                    'options' => $mail_tpls
                ],
            ]);

        });

        /*
        App::error(function(Exception $exception) {
            //Log::error($exception->getMessage());

            if(Request::ajax())
                return json_encode(['#flashMessages'=>$exception->getMessage()]);
            else
                return $exception->getMessage();
        });


        App::fatal(function($exception) {
            //return true;
            //throw new ApplicationException($exception->getMessage());
            //echo 'fatal';
            echo 'fatal';
            dd($exception->getMessage());
        });
        */

        Event::listen('longread.blocks.get', function($longread){
            $longread->registerBlocks($this);
        });
    }

    public function extendUser()
    {
//        User::extend(function (Model $model) {
//            $model->jsonable(array_merge(
//                $model->getJsonable(),
//                [
//                    'data'
//                ]
//            ));
//        });

//        Event::listen('backend.form.extendFields', function ($widget) {
//            if (!$widget->model instanceof User) {
//                return;
//            }

//            $fields = $widget->model->data;
//
//            foreach ($fields as $field) {
//
//            }
//
//            $widget->addFields([
//                'data' =>
//            ]);
//        });
    }

    public function register()
    {
	    $this->registerConsoleCommand('integer.up', InitialData::class);
        $this->registerConsoleCommand('integer.truncate', TruncateData::class);
	    $this->registerConsoleCommand('integer.messages', MessagesExport::class);
        $this->registerConsoleCommand('integer.add_email_tpl', AddEmailTpl::class);
        $this->registerConsoleCommand('integer.remove_email_tpl', RemoveEmailTpl::class);
        $this->registerConsoleCommand('integer.send_participation_url', SendParticipationUrl::class);
        $this->registerConsoleCommand('integer.update_tenders', UpdateTenders::class);
        $this->registerConsoleCommand('integer.remove_tender_files', RemoveTenderFiles::class);
        $this->registerConsoleCommand('integer.update_not_work', IntegerUpdate::class);
        $this->registerConsoleCommand('integer.update', DbUpdate::class);
        $this->registerConsoleCommand('integer.remove_files', RemoveFiles::class);
        $this->registerConsoleCommand('integer.remove_empty_files', RemoveFilesWOUser::class);
    }
}
