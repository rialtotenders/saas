<?php namespace Perevorot\Rialtotender\Models;

use Model;
use Lang;

/**
 * Model
 */
class Status extends Model
{
    use \October\Rain\Database\Traits\Validation;

    CONST TYPE_TENDERS = 1;
    CONST TYPE_BIDS = 2;
    CONST TYPE_QUALIFICATIONS = 3;
    CONST TYPE_DOCUMENTS = 4;

    public static $status_files = [
        self::TYPE_TENDERS => 'status',
        self::TYPE_BIDS => 'bids',
        self::TYPE_QUALIFICATIONS => 'qualifications',
        self::TYPE_DOCUMENTS => 'documents',
    ];

    public $implement = [
        'System.Behaviors.SettingsModel',
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /**
     * @var string
     */
    public $settingsCode = 'perevorot.rialtotender.status';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public $translatable = [
        'tender',
        'bid',
        'qualification',
        'document',
        'contract',
        'change',
        'lot_status'
    ];

    /*
     * Validation
     */
    public $rules = [
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */

    public function getTypeOptions()
    {
        return [
            self::TYPE_TENDERS => 'Тендер',
            self::TYPE_QUALIFICATIONS => 'Квалификация',
            self::TYPE_BIDS => 'Ставки',
            self::TYPE_DOCUMENTS => 'Документы',
        ];
    }

    public static function getStatuses($type, $filter = false, $lang = false)
    {
        $data = self::instance();

        if(isset($data->{$type}) && $data->{$type} != "")
        {
            if($lang) {
                $tmp = explode("\n", (trim($data->lang($lang)->{$type}) . "\n"));
            } else {
                $tmp = explode("\n", (trim($data->{$type}) . "\n"));
            }

            $statuses = [];

            foreach($tmp AS $v)
            {
                if(stripos($v, '=') !== FALSE) {

                    $status = explode("=", $v);

                    if(count($status) == 2) {

                        $status[0] = trim($status[0]);
                        $status[1] = trim($status[1]);

                        if($status[0] && $status[1]) {

                            if($filter) {
                                array_push($statuses, [
                                    'id' => $status[0],
                                    'name' => $status[1],
                                ]);
                            }
                            else
                            {
                                $statuses[$status[0]] = $status[1];
                            }
                        }
                    }
                }
            }
        }
        else
        {
            return [];
        }

        return $statuses;
    }

    public function getShowTypeAttribute()
    {
        return Lang::get('perevorot.rialtotender::status.'.$this->type);
    }

}