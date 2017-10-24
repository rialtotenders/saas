<?php

namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Traits\IconParser;

/**
 * Class Description
 * @package Perevorot\Rialtotender\Longread
 */
class Description extends LongreadComponentBase
{
    use IconParser;

    public function onRender()
    {
        $data = json_decode($this->property('value'));
        $descriptions_advantages = (array) $data->description_advantages;

        foreach ($descriptions_advantages as $item) {
            $item->repeater_icon = $this->getIconPath($item->repeater_icon);
        }

        return $this->partial([
            'data' => $data,
            'description_advantages' => $descriptions_advantages,
        ]);
    }
}
