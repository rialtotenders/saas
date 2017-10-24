<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Models\Area;

class Areas extends LongreadComponentBase
{
    public function onRender()
    {
        $areas = Area::available();
        $data = json_decode($this->property('value'));

        if ($data->areas_is_index) {
            $areas->index();
        }

        return $this->partial([
            'data' => $data,
            'areas' => $areas->sortable()->get(),
        ]);
    }
}
