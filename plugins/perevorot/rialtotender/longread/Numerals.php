<?php namespace Perevorot\Rialtotender\Longread;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Perevorot\Longread\Classes\LongreadComponentBase;

class Numerals extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));
        $numerals_numerals = (array) $data->numerals_numerals;
        $countTenders='';

        if(!empty(env('DB_HOST_2')))
        {
            $stat = (array) Cache::remember('count-tenders', 60, function () {
                return DB::connection('cbd')->table('z_dashboard')->get()->toArray();
            });

	    foreach ($numerals_numerals as $item) {
                foreach($stat as $s) {
                    $item->repeater_number = str_replace('{'.(!empty($s->indicator_name) ? $s->indicator_name : '').'}', $s->indicator_value, $item->repeater_number);
                    $item->repeater_description = str_replace('{'.$s->indicator_name.'}', $s->indicator_value, $item->repeater_description);
                }
            }
        }

        return $this->partial([
            'data' => $data,
            'w'=>round(100/sizeof($numerals_numerals)),
            'numerals_numerals' => $numerals_numerals,
        ]);
    }
}
