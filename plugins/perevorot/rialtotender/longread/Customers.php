<?php namespace Perevorot\Rialtotender\Longread;

use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Models\Customer;

class Customers extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));
        $customers = Customer::available();

        if ($data->customers_is_index) {
            $customers->index();
        }

        $customers = $customers->sortable()->get();

        foreach ($customers as $item) {
            $item->edrpou = str_replace("\r\n", '&edrpou=', $item->edrpou);
        }

        return $this->partial([
            'data' => $data,
            'customers' => $customers,
        ]);
    }
}
