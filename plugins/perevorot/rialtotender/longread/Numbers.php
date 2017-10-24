<?php namespace Perevorot\Rialtotender\Longread;

use Illuminate\Support\Facades\Cache;
use Perevorot\Longread\Classes\LongreadComponentBase;

class Numbers extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));
        $numbers_numerals = (array) $data->numbers_numerals;

        return $this->partial([
            'data' => $data,
            'w'=>round(100/sizeof($numbers_numerals)),
            'numbers_numerals' => $numbers_numerals,
        ]);
    }
}
