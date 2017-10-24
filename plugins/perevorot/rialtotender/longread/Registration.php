<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Traits\IconParser;
use System\Models\File;
use Request;

class Registration extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));

        if ($data->image) {
            $data->image=File::find($data->image->id);
        }

        return $this->partial([
            'data' => $data,
        ]);
    }
}
