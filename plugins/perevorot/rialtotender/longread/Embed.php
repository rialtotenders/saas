<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;

class Embed extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));
        $width='/(width)="[0-9]*"/i';
        $height='/(height)="[0-9]*"/i';

        $widthReplacement = 'width="' . $data->embed_width . '"';
        $heightReplacement = 'height="' . $data->embed_height .  '"';

        $data->embed_code=preg_replace($width, $widthReplacement, $data->embed_code);
        $data->embed_code=preg_replace($height, $heightReplacement, $data->embed_code);

        return $this->partial([
            'data' => $data,
        ]);
    }
}
