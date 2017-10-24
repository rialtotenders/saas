<?php namespace Perevorot\Rialtotender\Longread;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Perevorot\Longread\Classes\LongreadComponentBase;

class Table extends LongreadComponentBase
{
    public function onRender()
    {
        $data = (array)json_decode($this->property('value'));
        $data['table_fields'] = (array)$data['table_fields'];

        if(!isset($data['table_name']) && !isset($data['table_fields'])) {
            return null;
        }

        if(!empty(env('DB_HOST_2')))
        {
            $results = Cache::remember(md5($this->property('value')), 60, function () use($data) {
                return DB::connection('cbd')->select("select * from {$data['table_name']} " . ($data['table_sql'] ? " where {$data['table_sql']} " : ''));
            });

            foreach($results AS $k => $item) {
                $results[$k] = (array) $item;
            }
        }

        return $this->partial([
            'data' => $data,
            'results' => $results,
        ]);
    }
}
