<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Traits\IconParser;

class Advantages extends LongreadComponentBase
{
    use IconParser;

    public function onRender()
    {
        $data = json_decode($this->property('value'));
        $advantages_advantages = (array) $data->advantages_advantages;

        foreach ($advantages_advantages as $item) {
            $item->repeater_icon = $this->getIconPath($item->repeater_icon);
        }

        return $this->partial([
            'data' => $data,
            'w'=>round(100/(int)$data->advantages_inrow),
            'advantages_advantages' => $advantages_advantages,
        ]);
    }
}
