<?php

namespace Perevorot\Rialtotender\Classes;

use Config;
use Dotenv;
use DB;
use App;
use Illuminate\Foundation\Application;
use Illuminate\Mail\TransportManager;
use Illuminate\Support\Facades\Mail;
use System\Models\MailSetting;
use System\Models\MailTemplate;

class SetConfig
{
    public function setConnection($theme_folder = null, $db_connect_name = 'mysql')
    {
        $env = $this->getEnvAttribute($theme_folder);

        Config::set('database.connections.'.$db_connect_name, [
            'driver' => 'mysql',
            'host' => $env->DB_HOST,
            'port' => $env->DB_PORT,
            'database' => $env->DB_DATABASE,
            'username' => $env->DB_USERNAME,
            'password' => $env->DB_PASSWORD,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);

        DB::reconnect($db_connect_name);

        $mailSetting = DB::table('system_settings')->where('item', 'system_mail_settings')->first();

        if(!empty($mailSetting->value)) {
            $this->applyConfigValues(json_decode($mailSetting->value));
        }

        return $env;
    }

    public function applyConfigValues($settings)
    {
        $config = App::make('config');
        $config->set('mail.driver', $settings->send_mode);
        $config->set('mail.from.name', $settings->sender_name);
        $config->set('mail.from.address', $settings->sender_email);

        switch ($settings->send_mode) {

            case MailSetting::MODE_SMTP:
                $config->set('mail.host', $settings->smtp_address);
                $config->set('mail.port', $settings->smtp_port);
                if ($settings->smtp_authorization) {
                    $config->set('mail.username', $settings->smtp_user);
                    $config->set('mail.password', $settings->smtp_password);
                }
                else {
                    $config->set('mail.username', null);
                    $config->set('mail.password', null);
                }
                if ($settings->smtp_encryption) {
                    $config->set('mail.encryption', $settings->smtp_encryption);
                }
                else {
                    $config->set('mail.encryption', null);
                }
                break;

            case MailSetting::MODE_SENDMAIL:
                $config->set('mail.sendmail', $settings->sendmail_path);
                break;

            case MailSetting::MODE_MAILGUN:
                $config->set('services.mailgun.domain', $settings->mailgun_domain);
                $config->set('services.mailgun.secret', $settings->mailgun_secret);
                break;

            case MailSetting::MODE_MANDRILL:
                $config->set('services.mandrill.secret', $settings->mandrill_secret);
                break;

            case MailSetting::MODE_SES:
                $config->set('services.ses.key', $settings->ses_key);
                $config->set('services.ses.secret', $settings->ses_secret);
                $config->set('services.ses.region', $settings->ses_region);
                break;
        }

        //(new \Illuminate\Mail\MailServiceProvider(app()))->register();
    }

    public function getEnvAttribute($theme_folder)
    {
        if ($theme_folder) {
            $env_file = base_path() . '/integer/' . $theme_folder . '/.env';

            if (is_file($env_file)) {
                $value = file_get_contents($env_file);
            }
        } else {
            $value = file_get_contents(base_path() . '/.env');
        }

        return $this->readEnvFromString($value);
    }

    protected function readEnvFromString($string)
    {
        $array = explode("\n", (trim($string) . "\n"));
        $array = array_filter($array);
        $env = [];

        foreach ($array as $one) {
            $one = trim($one);

            if (!$one || stripos($one, '#') !== FALSE) {
                continue;
            }

            list($variable, $value) = explode('=', $one);

            if ($variable) {
                $env[$variable] = $value;
            }
        }

        return (object)$env;
    }
}
