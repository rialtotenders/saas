<?php namespace Perevorot\Rialtotender\Models;

use App;
use Model;
use Input;
use Lang;
use Illuminate\Support\Facades\Cache;

/**
 * Model
 */
class Classifier extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = [
        'System.Behaviors.SettingsModel',
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    /**
     * @var string
     */
    public $settingsCode = 'perevorot.rialtotender.classifier';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    public $translatable = [
        'cpv', 'measure'
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

    public static function getMeasurers($mcode = null)
    {
        $classifier = self::instance();
        $measurers = explode("\n", (trim($classifier->measure)."\n"));
        $items = [];

        if(count($measurers) > 0) {

            foreach ($measurers AS $key => $value) {

                if(stripos($value, '=') !== FALSE) {
                    $item = explode("=", $value);

                    if (count($item) == 2) {

                        if(stripos($item[1], '|') !== FALSE) {
                            $_item = explode("|", trim($item[1]));
                            $_item[0] = trim($_item[0]);
                            $_item[1] = trim($_item[1]);

                            if ($mcode && $mcode == $item[0]) {
                                return $_item[0];
                            }

                            $items[$item[0]] = $_item[1];
                        }

                    }
                }
            }
        }

        return $items;
    }

    public static function getCpvByCode($code = false)
    {
        if($code)
        {
            $classifier= self::instance();

            if(isset($classifier->cpv) && $classifier->cpv != "") {

                $classifier->cpv = trim($classifier->cpv) . "\n\r";

                preg_match_all('|'.$code.'[=].*\n|U',
                    $classifier->cpv,
                    $out, PREG_PATTERN_ORDER);

                if (!empty($out[0])) {
                    return explode("=", $out[0][0])[1];
                }
            }
        }

        return false;
    }

    public static function getAllCpv2_refactored($end_level = 5, $item_code = false)
    {
        return [];
        $classifier=self::instance();

        $array=[];

        if(!empty($classifier->cpv))
        {
            $classifier=explode("\r\n", trim($classifier->cpv));
            
            foreach($classifier as $one)
            {
                list($code, $name)=explode('=', $one);
                $section_code=rtrim(substr($code, 0, -2), '0');

                /*
                $root='s'.substr($section_code, 0, 2);
                $section=substr($section_code, 2);

                if($section)
                    $section=$root.'/'.implode('/', str_split(substr($section, 0, -1), 1));
                else
                    $section=$root;
                */
                $array[]=(object)[
                    'name'=>$name,
                    'code'=>$code,
                    'level'=>strlen($section_code)-2,
                ];
            }

            ksort($array);

            //return array_slice($array, 0, 200);
            return $array;

            $levels=[];

            foreach($array as $item)
                $item->level=self::getLevel($item->code);

            foreach($array as $k => $item)
            {
                if($item_code) {

                    $_item_code = substr($item_code, 0, stripos(substr($item_code, 1, 99), '0')+1);

                    if(stripos($item->code, $_item_code) !== 0) {
                        continue;
                    }
                }

                for($level=0;$level<=5;$level++)
                {
                    if($level+1==$item->level && $level+1 <= $end_level)
                    {
                        $item->children=substr($item->code, 0, 2+$level);
                        $levels[substr($item->code, 0, 1+$level)][$item->code]=$item;
                    }
                }
            }

            $array=[];

            foreach($levels as $items)
            {
                foreach($items as $item)
                {
                    if(!empty($levels[$item->children]))
                    {
                        $level=$levels[$item->children];

                        $item->{'sub_level'}=$level;
                    }
                }
            }

            $object=(object)[
                'level1'=>[]
            ];

            foreach($levels as $key=>$items)
            {
                foreach($items as $item)
                {
                    if($item->level==1)
                    {
                        $object->level1[$item->code]=(object)[
                            'code'=>$item->code,
                            'name'=>$item->name,
                            'sub_level'=>$item->sub_level
                        ];
                    }
                    elseif($item_code) {
                        if (empty($object->level1) && isset($item->sub_level)) {
                            $object->level1[$item->code] = (object)[
                                'code' => $item->code,
                                'name' => $item->name,
                                'sub_level' => $item->sub_level
                            ];
                        }
                    }
                }
            }

            $array = json_decode(json_encode($object), true);
        }

        return $array;
    }

    public static function getLevel($code) {
        return stripos(substr($code, 1, 99), '0');
    }

    public static function getAllCpv()
    {
        $classifier= self::instance();
        $data = [];

        if(isset($classifier->cpv) && $classifier->cpv != "")
        {
            $classifier->cpv = trim($classifier->cpv) . "\n\r";

            foreach(explode("\n", $classifier->cpv) AS $v)
            {
                $tmp = explode("=", $v);

                    if (count($tmp) == 2) {

                        $tmp[0] = trim($tmp[0]);
                        $tmp[1] = trim($tmp[1]);

                        if ($tmp[0] && $tmp[1]) {
                            array_push($data, [
                                'id' => $tmp[0],
                                'name' => $tmp[1]
                            ]);
                        }
                    }
                }
            }

        return $data;
    }

    public static function getCpv()
    {
        $json_level=Input::get('level') ? Input::get('level') : 1000000;
        $parent_item=Input::get('parent') ? rtrim(substr(Input::get('parent'), 0, -2), '0') : false;
        $locale = !empty(post('lang')) ? post('lang') : App::getLocale();

        $array=Cache::remember('cpv-'.md5($json_level.$parent_item.$locale), 60, function() use($parent_item, $json_level, $locale) {
            $classifier=self::instance();

            if($locale)
            {
                $_classifier = $classifier->lang($locale)->cpv;
            } else {
                $_classifier = $classifier->cpv;
            }

            unset($classifier);

            $array=[];
            $parent_level=0;
            $cnt=0;

            if(!empty($_classifier))
            {
                $by_level=[];
                $_classifier=explode("\r\n", trim($_classifier));
    
                foreach($_classifier as $k=>$one)
                {
                    list($code, $name)=explode('=', $one);
                    $section_code=rtrim(substr($code, 0, -2), '0');
    
                    if(strlen($section_code)==1)
                        $section_code.='0';
    
                    /*
                    $root='s'.substr($section_code, 0, 2);
                    $section=substr($section_code, 2);
    
                    if($section)
                        $section=$root.'/'.implode('/', str_split(substr($section, 0, -1), 1));
                    else
                        $section=$root;
                    */
                    $level=strlen($section_code)-2;
    
                    if($section_code==$parent_item)
                        $parent_level=$level;
    
                    $by_scode[$section_code]=$cnt;

                    if($level < $json_level){
                        if(!$parent_item || ($parent_item && starts_with($code, $parent_item) && $section_code!=$parent_item)) {
                            
                            $level=$level-($parent_item ? $parent_level+1 : 0);
    
                            $array[]=(object) [
                                'name'=>trim($name),
                                'id'=>$code,
                                'scode'=>$section_code,
                                'level'=>$level,
                                'branch'=>[],
                                'index'=>$cnt
                            ]; 

                            $cnt++;                       
                        }
                    }
                }
    
                foreach($array as $k=>$item)
                {
                    $branch=[];
                    
                    for($i=1;$i<=$item->level;$i++)
                    {
                        $scode=substr($item->scode, 0, -$i);
    
                        if(array_key_exists($scode, $by_scode))
                            $branch[]=$by_scode[$scode];
                    }
    
                    unset($item->scode);
    
                    $item->branch=$branch;
                }
            }
            
            return $array;
        });

        return $array;
    }
}