<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use System\Models\File;

class Quote extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));

        if(!empty($data->image->id)) {
            $data->image=File::find($data->image->id);
        }

        return $this->partial([
            'data' => $data
        ]);
    }
}
