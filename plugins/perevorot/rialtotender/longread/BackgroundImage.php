<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use System\Models\File;

class BackgroundImage extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));

        if(!empty($data->background_image_image->id)) {
            $data->background_image_image=File::find($data->background_image_image->id);
        }

        return $this->partial([
            'data' => $data
        ]);
    }
}
