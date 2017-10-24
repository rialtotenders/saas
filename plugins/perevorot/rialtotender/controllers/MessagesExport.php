<?php namespace Perevorot\Rialtotender\Controllers;

use Perevorot\Rialtotender\Models\MessagesExportExclude;
use System\Helpers\Cache as CacheHelper;
use System\Classes\SettingsManager;
use RainLab\Translate\Models\Locale;
use Backend\Classes\Controller;
use ApplicationException;
use BackendMenu;
use Input;
use Flash;
use Str;
use DB;

class MessagesExport extends Controller
{
    public $requiredPermissions = [
        'rialtotender.messagesexport_permission'
    ];
    
    public $locales;
    public $importLocale;
    public $importMessages;
    public $importMessagesExists;
    public $pageTitle='Обмен сообщениями';
    public $messages_exclude;
    
    public function __construct()
    {
        parent::__construct();
        
        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Perevorot.Rialtotender', 'messagesexport');

    }
    
    public function index()
    {
        $settings=MessagesExportExclude::instance();

        $this->messages_exclude=$settings->messages;

        $this->locales=Locale::listAvailable();
    }
    
    public function onExport()
    {
        $locale=Input::get('locale');

        return [
            'export'=>json_encode([
                $locale => $this->getMessagesByLocale($locale)
            ], JSON_UNESCAPED_UNICODE)
        ];
    }
    
    private function getMessagesByLocale($locale)
    {
        $messages=DB::table('rainlab_translate_messages')->get();

        $data=[];

        foreach($messages as $message)
        {
            $json=json_decode($message->message_data);
            $value=!empty($json->{$locale}) ? $json->{$locale} : '';

            $data[$message->code]=$value;
        }
        
        return $data;
    }

    public function onImport()
    {
        $messages=Input::get('messages');
        $messages=json_decode($messages, true);

        if(empty($messages))
            throw new ApplicationException('Ошибка формата сообщений для импорта');

        foreach($messages as $key=>$value)
        {
            $locale=$key;
            $data=$value;
        }

        $settings=MessagesExportExclude::instance();

        $messages_exclude=explode("\r\n", $settings->messages);
        array_walk($messages_exclude, 'trim');
        
        $this->messages_exclude=$messages_exclude;
        $this->importLocale=$locale;
        $this->importMessages=$data;
        $this->importMessagesExists=$this->getMessagesByLocale($locale);
    }

    public $importTotal;
    
    public function onImportConfirm()
    {
        $messages=Input::get('messages');
        $checked=Input::get('checked');
        $messages=json_decode($messages);

        if(empty($checked))
            throw new ApplicationException('Выберите метки для импорта');

        if(empty($messages))
            throw new ApplicationException('Ошибка формата сообщений для импорта');

        $exists=DB::table('rainlab_translate_messages')->get();
        $this->importTotal=0;
        
        foreach($messages as $locale=>$data)
        {
            foreach($data as $code=>$message)
            {
                if(!in_array($code, $checked))
                    continue;

                $found=false;
                
                foreach($exists as $item)
                {
                    $json=json_decode($item->message_data);

                    if(!empty(trim($message))){
                        $json->{$locale}=$message;
                    }

                    if($item->code==$code)
                    {
                        DB::table('rainlab_translate_messages')->where('code', $item->code)->update([
                            'message_data' => json_encode($json)
                        ]);

                        $this->importTotal++;
                        $found=true;
                    }
                }

                if(!$found){
                    $json=(object)[
                        'x'=>$this->makeMessageCode($code)
                    ];
                    
                    if(!empty(trim($message))){
                        $json->{$locale}=$message;
                    }

                    DB::table('rainlab_translate_messages')->insert([
                        'code'=>$code,
                        'message_data' => json_encode($json)
                    ]);
                }
            }
        }
        
        CacheHelper::clear();
        Flash::success('Сообщения сохранены');
    }
    
    public function onExcludeSave()
    {
        $settings=MessagesExportExclude::instance();

        $settings->messages=Input::get('messages_exclude');
        $settings->save();

        Flash::success('Сообщения сохранены');
    }
    
    private function makeMessageCode($messageId)
    {
        $separator = '.';

        // Convert all dashes/underscores into separator
        $messageId = preg_replace('!['.preg_quote('_').'|'.preg_quote('-').']+!u', $separator, $messageId);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $messageId = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($messageId));

        // Replace all separator characters and whitespace by a single separator
        $messageId = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $messageId);

        return Str::limit(trim($messageId, $separator), 250);
    }
}