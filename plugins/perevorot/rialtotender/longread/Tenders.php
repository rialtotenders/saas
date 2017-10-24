<?php namespace Perevorot\Rialtotender\Longread;

use Illuminate\Database\Eloquent\Collection;
use Perevorot\Longread\Classes\LongreadComponentBase;
use Perevorot\Rialtotender\Models\Area;

class Tenders extends LongreadComponentBase
{
    public function onRender()
    {
        $data = json_decode($this->property('value'));

        if($data->edrpou) {
            $edrpou = 'edrpou='.str_replace(',', '&edrpou=', $data->edrpou);
            $params = explode('&', $edrpou);
            $searchComponent = app('Perevorot\Form\Components\SearchResult');
            $searchComponent->search_type = 'tender';
            $tenders = $searchComponent->getSearchResults($params, null, 3);
            $data=json_decode($tenders);

            if(!empty($data->items))
            {
                $callback=camel_case('prepare_'.$searchComponent->search_type);
                $templateData=$searchComponent->$callback($data);
            }
            elseif(empty($data) || (property_exists($data, 'items') && is_array($data->items) && !sizeof($data->items)))
            {
                $templateData['noresults']=true;
            }
            elseif(!empty($data->error))
            {
                $templateData['error']=$data->error;
            }

            $templateData['search_type']=$searchComponent->search_type;
            $templateData['edrpou']=$edrpou;
            $html = $this->renderPartial('longread/tenders/results.htm', $templateData);
        } else {
            $html = '';
        }

        return $this->partial([
            'tenders' => $html,
        ]);
    }
}
