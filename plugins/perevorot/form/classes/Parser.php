<?php

namespace Perevorot\Form\Classes;

use Cms\Classes\Theme;
use Perevorot\Rialtotender\Models\Procurement;
use Perevorot\Rialtotender\Models\Status;
use Illuminate\Support\Facades\Cache;
use October\Rain\Support\Facades\Yaml;
use October\Rain\Support\Traits\Singleton;
use Perevorot\Users\Facades\Auth;
use Perevorot\Users\Traits\UserSetting;
use Symfony\Component\Yaml\Exception\ParseException;
use DateTime;
use Request;

/**
 * Class TenderParser
 * @package Perevorot\Form\Classes
 */
class Parser
{
    use Singleton, UserSetting;

    public function plan_parse($id, $key='pid', $test = false)
    {
        $this->search_type='plan';
        $this->error=false;
        //$gos_tender = stripos($id, 'R-') === FALSE;
        $json = json_decode($this->getSearchResults([$key.'='.$id], $test, false));

        if(!isset($json->items) || sizeof($json->items) == 0)
        {
            $json = json_decode($this->getSearchResults([$key.'='.$id], $test, true));
        }

        if(!isset($json->items) || sizeof($json->items) == 0) {
            return false;
        }

        $item = current($json->items);

        if($item->tender->procurementMethod) {
            $item->tender->__procurementMethod = Procurement::getData('_type', $item->tender->procurementMethod);
        }

        if($item->tender->procurementMethodType) {
            $item->tender->__procurementMethodType = Procurement::getData('_method_type', $item->tender->procurementMethodType);
        }

        if(isset($_GET['dump']) && getenv('APP_ENV')=='local')
            dd($item);

        return $item;
    }

    /**
     * @param $id
     * @return bool|mixed
     */
    public function tender_parse($id, $key='tid', $test = false, $cron = false)
    {
        $this->search_type='tender';
        $this->error=false;
        //$gos_tender = stripos($id, 'R-') === FALSE;

        if(!$cron) {
            $json = json_decode($this->getSearchResults([$key . '=' . $id], $test, false));
        } else {
            $json = json_decode($this->getSearchResultsForCron([$key . '=' . $id], $test, false, $cron));
        }

        if(!isset($json->items) || sizeof($json->items) == 0)
        {
            if(!$cron) {
                $json = json_decode($this->getSearchResults([$key . '=' . $id], $test, true));
            } else {
                $json = json_decode($this->getSearchResultsForCron([$key . '=' . $id], $test, true, $cron));
            }
        }

        if(!isset($json->items) || sizeof($json->items) == 0) {
            return false;
        }

        $item=false;

        if($json)
        {
            $data=$json;

            if(empty($data->error))
            {
                $item=array_first($data->items, function($one, $k) use ($id, $key) {
                    return ($key=='tid' && $one->tenderID===$id) || ($key=='id' && $one->id===$id);
                });
            }
            else
                $this->error=$data->error;

            if(!$item)
                $this->error='Тендер не найден';

            if($this->error)
            {
                return false;
                /*
                return $this->renderPartial('form/tender.htm', [
                    'item' => false,
                    'error' => $this->error
                ]);*/
            }
        } else {
            \IntegerLog::info('tender.parse', ('Tender not found: ' . $id));
            return false;
        }

        if(empty($item->procurementMethodType))
        {
            $item->procurementMethodType=new \StdClass();
            $item->procurementMethodType='';
        }

        $this->get_print_href($item);
        $this->get_multi_lot($item);
        $this->get_single_lot($item);

        $this->get_eu_lots($item);

        if(!empty($item->awards))
        {
            usort($item->awards, function ($a, $b)
            {
                $datea = new \DateTime($a->date);
                $dateb = new \DateTime($b->date);

                return $datea>$dateb;
            });
        }

        $features_price=1;

        if(!empty($item->features))
        {
            $tender_features=array_where($item->features, function($feature, $key) use ($item){
                return $feature->featureOf=='lot' || $feature->featureOf=='item' || $feature->featureOf=='tenderer' || (!empty($item->lots) && sizeof($item->lots)==1 && ($feature->featureOf=='lot' || $feature->featureOf=='tenderer'));
            });
        }

        if(!empty($tender_features))
        {
            foreach($tender_features as $k=>$feature)
            {
                $max=0;

                foreach($feature->enum as $one)
                    $max=max($max, floatval($one->value));

                $tender_features[$k]->max = new \stdClass();
                $tender_features[$k]->max=$max;

                $features_price-=$max;

                usort($feature->enum, function ($a, $b)
                {
                    return strcmp($b->value, $a->value);
                });

                $tender_features[$k]->enum=$feature->enum;
            }

            $item->__features=$tender_features;
            $item->__tender_features=array_where($tender_features, function($feature, $key) use($item) {
                return $feature->featureOf=='tenderer' || ($feature->featureOf=='item' && !array_first($item->items, function($_item, $ikey) use($feature) {
                        return $feature->relatedItem == $_item->id && isset($_item->relatedLot);
                    }));
            });
            $item->__lot_features=array_where($tender_features, function($feature, $key) use($item){
                return $feature->featureOf=='lot' || ($feature->featureOf=='item' && array_first($item->items, function($_item, $ikey) use($item, $feature) {
                        return $feature->relatedItem == $_item->id && isset($_item->relatedLot) && array_first($item->lots, function($lot, $lkey) use($_item) {
                            return $lot->id == $_item->relatedLot;
                        });
                    }));
            });
        }

        if(!empty($item->lots) && sizeof($item->lots)==1 && !empty($item->bids))
        {
            foreach($item->bids as $k=>$one)
            {
                $item->bids[$k]->value=new \StdClass();
                $item->bids[$k]->value=!empty($one->lotValues) ? head($one->lotValues)->value : 0;
            }
        }

        if(!$item->__isMultiLot)
        {
            if($features_price<1 && !empty($item->bids))
            {
                foreach($item->bids as $k=>$bid)
                {
                    $item->bids[$k]->__featured_coef=new \StdClass();
                    $item->bids[$k]->__featured_coef=null;

                    $item->bids[$k]->__featured_price=new \StdClass();
                    $item->bids[$k]->__featured_price=null;

                    if(!empty($bid->parameters))
                    {
                        $featured_coef=trim(number_format(1+array_sum(array_pluck($bid->parameters, 'value'))/$features_price, 10, '.', ' '), '.0');

                        $item->bids[$k]->__featured_coef=$featured_coef;
                        $item->bids[$k]->__featured_price=isset($bid->value->amount) ? str_replace('.00', '', number_format($bid->value->amount/$featured_coef, 2, '.', ' ')) : 0;
                    }
                }
            }

            if($features_price<1 && !empty($item->bids))
            {
                usort($item->bids, function ($a, $b)
                {
                    return floatval($a->__featured_price)>floatval($b->__featured_price);
                });
            }
            elseif(!empty($item->bids))
            {
                usort($item->bids, function ($a, $b) use ($features_price)
                {
                    return empty($a->value) || empty($b->value) || (floatval($a->value->amount)>floatval($b->value->amount));
                });
            }
        }

        $item->__features_price=new \StdClass();
        $item->__features_price=$features_price;

        $this->parse_eu($item);

        $item->__icon=new \StdClass();
        $item->__icon=starts_with($item->tenderID, 'ocds-random-ua')?'pen':'mouse';

        $this->get_active_apply($item);
        $this->get_contracts($item, !empty($item->contracts) ? $item->contracts : false);
        $this->get_contracts_changes($item, !empty($item->contracts) ? $item->contracts : false);
        $this->get_contracts_ongoing($item, !empty($item->contracts) ? $item->contracts : false);
        $this->get_signed_contracts($item);
        $this->get_initial_bids($item);
        $this->get_initial_bids_dates($item);
        $this->get_yaml_documents($item);
        $this->get_tender_documents($item);
        $this->get_bids($item);
        $this->get_awards($item);
        $this->get_uniqie_awards($item);
        $this->get_uniqie_bids($item);
        $this->get_claims($item);
        $this->get_complaints($item);
        $this->get_opened_questions($item);
        $this->get_opened_claims($item);
        $this->get_questions($item);
        $this->get_qualifications($item);
        $this->get_lots($item);
        $this->get_procedure($item);
        $this->get_open_title($item);
        $this->parse_is_sign($item);
        $this->get_cancellations($item);
        $this->get_action_url_singlelot($item);
        $this->get_auction_period($item);
        $this->get_auction_period_multilot($item);
        $this->get_button_007($item, $item->procuringEntity);
        $this->get_stage2TenderID($item);
        $this->get_stage1TenderID($item);

        $item->__amount=str_replace('.00', '', number_format($item->value->amount, 2, '.', ' '));

        $statuses=Status::getStatuses('tender');

        $item->__status_name = isset($statuses[$item->status]) ? $statuses[$item->status] : $item->status;

        $address=[];

        foreach(['postalCode', 'countryName', 'region', 'locality', 'streetAddress'] as $a)
        {
            if(!empty($item->procuringEntity->address->{$a}))
                $address[]=$item->procuringEntity->address->{$a};
        }

        $item->procuringEntity->__address=implode(', ', $address);

        if(isset($_GET['dump']) && getenv('APP_ENV')=='local')
            dd($item);

        return $item;
    }

    public function getSearchResultsForCron($query, $test = false, $gos_tender = null, $env)
    {
        $user_mode = $this->checkUserMode(Auth::getUser(), $gos_tender, $test);

        if(!isset($env['API_'.$user_mode.strtoupper($this->search_type)])) {
            return null;
        }

        $url=$env['API_'.$user_mode.strtoupper($this->search_type)].'?'.implode('&', $query);

        if(isset($_GET['api']) && getenv('APP_ENV')=='local')
            dump($url);

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        if(env('API_'.$user_mode.'LOGIN') && env('API_'.$user_mode.'PASSWORD')){
            curl_setopt($ch, CURLOPT_USERPWD, env('API_'.$user_mode.'LOGIN') . ":" . env('API_'.$user_mode.'PASSWORD'));
        }

        $result=curl_exec($ch);

        curl_close($ch);

        if(isset($_GET['api']) && getenv('APP_ENV')=='local')
            dd(json_decode($result));

        return $result;
    }

    var $search_type;

    public function getSearchResults($query, $test = false, $gos_tender = null)
    {
        if(env('API_PRETEND'))
            return file_get_contents('./storage/pretend/tender.json');

        $user_mode = $this->checkUserMode(Auth::getUser(), $gos_tender, $test);

        if(in_array('tender', Request::segments())) {
            $this->search_type = 'tender';
        }
        elseif(in_array('plan', Request::segments())) {
            $this->search_type = 'plan';
        }

        if(env('API_'.$user_mode.'QUERY'.($this->search_type=='plan'?'_PLAN':'_TENDER'))){
            $query[]=env('API_'.$user_mode.'QUERY'.($this->search_type=='plan'?'_PLAN':'_TENDER'));
        }

        $url=env('API_'.$user_mode.strtoupper($this->search_type)).'?'.implode('&', $query);

        if(isset($_GET['api']) && getenv('APP_ENV')=='local')
            dump($url);

        $ch=curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        if(env('API_'.$user_mode.'LOGIN') && env('API_'.$user_mode.'PASSWORD')){
            curl_setopt($ch, CURLOPT_USERPWD, env('API_'.$user_mode.'LOGIN') . ":" . env('API_'.$user_mode.'PASSWORD'));
        }

        $result=curl_exec($ch);

        curl_close($ch);

        if(isset($_GET['api']) && getenv('APP_ENV')=='local')
            dd(json_decode($result));

        return $result;
    }

    public function get_auction_period_multilot(&$item)
    {
        if(!empty($item->lots) && sizeof($item->lots)>1)
        {
            $item->__lotAuctionPeriod=new \StdClass();

            $lot = array_first($item->lots, function($lot, $key) {
                return isset($lot->auctionPeriod);
            });

            $item->__lotAuctionPeriod=isset($lot->auctionPeriod) ? $lot->auctionPeriod : null;
        }
    }

    public function get_auction_period(&$item)
    {
        if(!empty($item->lots) && sizeof($item->lots)==1 && !empty($item->lots[0]->auctionUrl) && empty($item->auctionPeriod))
        {
            $item->auctionPeriod=new \StdClass();
            $item->auctionPeriod=$item->lots[0]->auctionPeriod;
        }
    }

    public function get_print_href(&$item)
    {
        $item->__print_href=new \StdClass();
        $item->__print_href=false;

        if(!empty($item->procurementMethodType))
        {
            if(in_array($item->procurementMethod, ['open', 'selective']) && $item->procurementMethodType!='belowThreshold')
                $item->__print_href='open';

            if($item->procurementMethod=='limited' && $item->procurementMethodType!='reporting')
                $item->__print_href='limited';

            if($item->procurementMethod=='limited' && $item->procurementMethodType=='reporting')
                $item->__print_href='limited-reporting';
        }
    }

    public function getSearchResultsHightlightArray($query)
    {
        $query_string=$query;
        $highlight=[];

        if($query_string)
        {
            $query_array=explode('&', urldecode($query_string));

            if(sizeof($query_array))
            {
                foreach($query_array as $item)
                {
                    $item=explode('=', $item);

                    $source=$item[0];

                    if($source=='query')
                    {
                        $search_value=$item[1];
                        $highlight[]=$item[1];

                        $value=$this->get_value($source, $search_value);
                        $highlight[]=$value;
                    }
                }
            }

            $highlight=array_unique(array_filter($highlight));
        }

        return $highlight;
    }

    var $platforms=[];

    private function get_active_apply(&$item)
    {
        $item->__is_apply=new \stdClass();
        $item->__is_apply=in_array($item->status, ['active.enquiries', 'active.tendering']);

        $platforms=[];

        if($item->procurementMethodType=='belowThreshold')
        {
            $platforms=array_where($this->platforms, function($platform, $key){
                return $platform['level2']==true;
            });
        }
        elseif($item->procurementMethodType!='belowThreshold' && $item->procurementMethodType!='reporting')
        {
            $platforms=array_where($this->platforms, function($platform, $key){
                return $platform['level4']==true;
            });
        }

        shuffle($platforms);

        $item->__is_apply_platforms=$platforms;
    }


    private function get_value($source, $search_value)
    {
        $lang='ua';

        $data=[];

        if(!file_exists(public_path().'/themes/rialtotender/resources/json/'.$lang.'/'.$source.'.json'))
            return $data;

        $raw=Cache::rememberForever('json_ua_'.$source, function() use ($lang, $source) {
            return json_decode(file_get_contents(public_path().'/themes/rialtotender/resources/json/'.$lang.'/'.$source.'.json'), TRUE);
        });

        foreach($raw as $id=>$name)
        {
            array_push($data, [
                'id'=>$id,
                'name'=>$name
            ]);
        }

        foreach($data as $item)
        {
            if($item['id']==$search_value)
                return $item['name'];
        }

        return FALSE;
    }

    private function parseBiNumbers($numbers)
    {
        $out=Cache::remember('bi_numbers', 60, function() use ($numbers)
        {
            foreach($numbers as $name=>$number)
            {
                $data=file_get_contents($number);

                if(!empty($data))
                {
                    $data=explode("\n", trim($data));

                    $out[$name]=$data;
                }
                else
                    $out[$name]=[0, ''];
            }

            return $out;
        });

        return $out;
    }

    private function get_initial_bids(&$item)
    {
        $bidders_by_lot=[];
        $bid_by_bidders=[];

        if(!empty($item->bids))
        {
            foreach($item->bids as $bid)
                $bid_by_bidders[$bid->id]=0;
        }

        if(!empty($item->documents))
        {
            foreach($item->documents as $document)
            {
                if(pathinfo($document->title, PATHINFO_EXTENSION)=='yaml' && !empty($document->url))
                {
                    try
                    {
                        $yaml=Cache::remember('yaml_'.md5($document->url), 60, function() use ($document){
                            $yaml_file=file_get_contents($document->url);

                            return !empty($yaml_file) ? Yaml::parse($yaml_file) : [];
                        });

                        if(!empty($yaml['timeline']['auction_start']['initial_bids']))
                        {
                            foreach($yaml['timeline']['auction_start']['initial_bids'] as $bid)
                            {
                                if(!empty($yaml['lot_id']))
                                    $bidders_by_lot[$yaml['lot_id']][$bid['bidder']]=$bid['amount'];

                                $bid_by_bidders[$bid['bidder']]=$bid['amount'];
                            }
                        }
                    }
                    catch (ParseException $e) {}
                }
            }
        }

        $item->__initial_bids=new \StdClass();
        $item->__initial_bids=$bid_by_bidders;

        $item->__initial_bids_by_lot=new \StdClass();
        $item->__initial_bids_by_lot=$bidders_by_lot;
    }

    private function get_initial_bids_dates(&$item)
    {
        $bidders_by_lot=[];
        $bid_by_bidders=[];

        if(!empty($item->bids))
        {
            foreach($item->bids as $bid)
                $bid_by_bidders[$bid->id]=0;
        }

        if(!empty($item->documents))
        {
            foreach($item->documents as $document)
            {
                if(pathinfo($document->title, PATHINFO_EXTENSION)=='yaml' && !empty($document->url))
                {
                    try
                    {
                        $yaml=Cache::remember('yaml_'.md5($document->url), 60, function() use ($document){
                            $yaml_file=file_get_contents($document->url);

                            return !empty($yaml_file) ? Yaml::parse($yaml_file) : [];
                        });

                        if(!empty($yaml['timeline']['auction_start']['initial_bids']))
                        {
                            foreach($yaml['timeline']['auction_start']['initial_bids'] as $bid)
                            {
                                if(!empty($yaml['lot_id']))
                                    $bidders_by_lot[$yaml['lot_id']][$bid['bidder']]=$bid['date'];

                                $bid_by_bidders[$bid['bidder']]=$bid['date'];
                            }
                        }
                    }
                    catch (ParseException $e) {}
                }
            }
        }

        $item->__initial_bids_dates=new \StdClass();
        $item->__initial_bids_dates=$bid_by_bidders;

        $item->__initial_bids_dates_by_lot=new \StdClass();
        $item->__initial_bids_dates_by_lot=$bidders_by_lot;
    }

    private function get_action_url_singlelot(&$item)
    {
        if(!empty($item->lots) && sizeof($item->lots)==1 && !empty($item->lots[0]->auctionUrl))
        {
            $item->auctionUrl=new \StdClass();
            $item->auctionUrl=$item->lots[0]->auctionUrl;
        }
    }

    private function get_multi_lot(&$item)
    {
        $item->__isMultiLot=new \StdClass();
        $item->__isMultiLot=(!empty($item->lots) && sizeof($item->lots)>1);
    }

    private function get_single_lot(&$item)
    {
        $item->__isSingleLot=new \StdClass();
        $item->__isSingleLot=(!empty($item->lots) && sizeof($item->lots)==1) || empty($item->lots);
    }

    private function get_opened_questions(&$item)
    {
        $item->__isOpenedQuestions=new \StdClass();
        $item->__isOpenedQuestions=false;

        if(!empty($item->__complaints_claims))
        {
            $claims=array_unique(array_pluck($item->__complaints_claims, 'status'));

            if(sizeof(array_intersect(['claim', 'ignored'], $claims)) > 0)
                $item->__isOpenedQuestions=true;
        }

        if(!$item->__isOpenedQuestions && !empty($item->questions))
        {
            $questions=array_where($item->questions, function($question, $key){
                return empty($question->answer);
            });

            if(sizeof($questions))
                $item->__isOpenedQuestions=true;
        }
    }

    private function get_opened_claims(&$item)
    {
        $item->__isOpenedClaims=new \StdClass();
        $item->__isOpenedClaims=false;

        if(!empty($item->__complaints_complaints))
        {
            $complaints=array_pluck($item->__complaints_complaints, 'status');

            if(sizeof(array_intersect(['pending', 'accepted', 'stopping'], $complaints)) > 0)
                $item->__isOpenedClaims=true;
        }
    }

    private function get_uniqie_awards(&$item)
    {
        $item->__unique_awards=new \StdClass();
        $item->__unique_awards=null;

        if(!empty($item->awards))
        {
            $ids=[];

            foreach($item->awards as $award)
            {
                foreach($award->suppliers as $supplier)
                {
                    array_push($ids, $supplier->identifier->id);
                }
            }

            $ids=array_unique($ids);
            $item->__unique_awards=sizeof($ids);
        }
    }

    private function get_awards(&$item)
    {
        $item->__active_award=new \StdClass();
        $item->__active_award=null;
        $count_unsuccessful_awards=0;

        if(!empty($item->awards))
        {
            foreach($item->awards as $award)
            {
                if($award->status=='active')
                    $item->__active_award=$award;

                if($award->status=='unsuccessful')
                    $count_unsuccessful_awards++;

                if($award->status=='cancelled')
                    $count_unsuccessful_awards++;
            }

            if($count_unsuccessful_awards==sizeof($item->awards))
                $item->__unsuccessful_awards=true;
        }

        /*
        $work_days=$this->parse_work_days();

        if(!empty($item->__active_award->complaintPeriod->endDate))
        {
            $date=date_create($item->__active_award->complaintPeriod->endDate);
            $sub_days=0;

            if(in_array($item->procurementMethodType, ['aboveThresholdUA', 'aboveThresholdEU', 'negotiation']))
                $sub_days=10;

            elseif(in_array($item->procurementMethodType, ['negotiation.quick']))
                $sub_days=5;

            elseif(in_array($item->procurementMethodType, ['belowThreshold']))
                $sub_days=2;

            if(in_array($item->procurementMethodType, ['belowThreshold', 'aboveThresholdUA.defense']))
            {
                $now=new DateTime();

                for($i=0;$i<$sub_days;$i++)
                {
                    $now->sub(new \DateInterval('P1D'));

                    if(in_array($now->format('Y-m-d'), $work_days))
                    {
                        $i--;
                        $sub_days++;
                    }
                }
            }

        }
        */

        if(!empty($item->__active_award))
            $item->__active_award->__date=date('d.m.Y H:i', strtotime($item->__active_award->date));

        if(!empty($item->__isMultiLot))
            $item->__active_award=null;
    }

    private function get_claims(&$item, $__type='tender', $return=false)
    {
        $__complaints_claims=[];

        foreach(['complaints', 'qualifications/complaints', 'awards/complaints'] as $type)
        {
            $path=explode('/', $type);

            if(!empty($item->{$path[0]}))
            {
                $array=$item->{$path[0]};
                $found_claims=[];

                if(sizeof($path)>1)
                {
                    foreach($array as $item_claim)
                    {
                        if(!empty($item_claim->{$path[1]}))
                            $found_claims=array_merge($found_claims, $item_claim->complaints);
                    }
                }
                else
                    $found_claims=$array;

                $data=array_where($found_claims, function($claim, $key) use($path, $item, $__type){
                    return $claim->type=='claim' && (empty($claim->questionOf) || ($claim->questionOf==$__type || ($claim->questionOf=='lot' && !$item->__isMultiLot)));
                });

                if($data)
                    $__complaints_claims=array_merge($__complaints_claims, $data);
            }

            if($__complaints_claims)
                $__complaints_claims=array_values($__complaints_claims);

            $__complaints_claims=array_where($__complaints_claims, function($claim, $key){
                return $claim->status!='draft';
            });

            foreach($__complaints_claims as $k=>$claim)
            {
                $__complaints_claims[$k]->__documents_owner=empty($claim->documents)?false:array_where($claim->documents, function($document, $key){
                    return in_array($document->author, ['complaint_owner']);
                });

                $__complaints_claims[$k]->__documents_tender_owner=empty($claim->documents)?false:array_where($claim->documents, function($document, $key){
                    return in_array($document->author, ['tender_owner']);
                });

                $__complaints_claims[$k]->__status_name=trans('tender.complain_statuses.'.$claim->status);
            }

            if(!$return)
            {
                $item->__complaints_claims=new \StdClass();
                $item->__complaints_claims=$__complaints_claims;
            }
            else
                return $__complaints_claims;
        }
    }

    private function get_complaints(&$item, $__type='tender', $return=false)
    {
        $__complaints_complaints=[];

        foreach(['complaints', 'qualifications/complaints', 'awards/complaints'] as $type)
        {
            $path=explode('/', $type);

            if(!empty($item->{$path[0]}))
            {
                $array=$item->{$path[0]};
                $found_complaints=[];

                if(sizeof($path)>1)
                {
                    foreach($array as $item_complaint)
                    {
                        if(!empty($item_complaint->{$path[1]}))
                            $found_complaints=array_merge($found_complaints, $item_complaint->complaints);
                    }
                }
                else
                    $found_complaints=$array;

                $data=array_where($found_complaints, function($complaint, $key) use($path, $item, $__type){
                    return $complaint->type=='complaint'&& (empty($complaint->questionOf) || ($complaint->questionOf==$__type || ($complaint->questionOf=='lot' && !$item->__isMultiLot)));
                });

                if($data)
                    $__complaints_complaints=array_merge($__complaints_complaints, $data);
            }
        }

        if(sizeof($__complaints_complaints))
        {
            foreach($__complaints_complaints as $k=>$complaint)
            {
                if(!empty($complaint->documents))
                {
                    $__complaints_complaints[$k]->__documents_owner=new \StdClass();
                    $__complaints_complaints[$k]->__documents_owner=array_where($complaint->documents, function($document, $key){
                        return $document->author=='complaint_owner';
                    });

                    $__complaints_complaints[$k]->__documents_reviewer=new \StdClass();
                    $__complaints_complaints[$k]->__documents_reviewer=array_where($complaint->documents, function($document, $key){
                        return in_array($document->author, ['aboveThresholdReviewers', 'reviewers']);
                    });
                }
            }

            $__complaints_complaints=array_values($__complaints_complaints);
        }

        foreach($__complaints_complaints as $k=>$complain)
        {
            $key=($item->procurementMethodType!='belowThreshold' ? '!' : '').'belowThreshold';
            $status_key=$complain->status;

            if($complain->status=='stopping')
                $status_key=$complain->status.(!empty($complain->dateAccepted) ? '+' : '-').'dateAccepted';

            $__complaints_complaints[$k]->__status_name=trans('tender.complaints_statuses.'.$key.'.'.$status_key);
        }

        $__complaints_complaints=array_where($__complaints_complaints, function($complain, $key){
            return $complain->status!='draft';
        });

        if(!$return)
        {
            $item->__complaints_complaints=new \StdClass();
            $item->__complaints_complaints=$__complaints_complaints;
        }
        else
            return $__complaints_complaints;

    }

    private function get_questions(&$item, $type='tender', $return=false)
    {
        if(!empty($item->questions))
        {
            $questions=array_where($item->questions, function($question, $key) use ($item, $type){
                return $question->questionOf==$type || !$item->__isMultiLot;
            });

            if(!$return)
            {
                $item->__questions=new \StdClass();
                $item->__questions=array_values($questions);
            }
            else
                return $questions;
        }

        if($return)
            return [];
    }

    private function get_questions_lots($item, $lot)
    {
        if(!empty($item->questions))
        {
            $item_ids=[];

            if(!empty($lot->__items))
                $item_ids=array_pluck($lot->__items, 'id');

            $questions=array_where($item->questions, function($question, $key) use ($item, $lot, $item_ids){
                return !empty($question->relatedItem) && ($question->questionOf=='lot' && $question->relatedItem==$lot->id) || ($question->questionOf=='item' && in_array($question->relatedItem, $item_ids));
            });

            return array_values($questions);
        }

        return [];
    }

    private function get_tender_documents(&$item, $type='tender')
    {
        if(!empty($item->documents))
        {
            $item->__tender_documents=new \StdClass();

            if($type=='tender' && (empty($item->lots) || (!empty($item->lots) && sizeof($item->lots)==1)))
                $type=['tender', 'lot', 'item'];
            elseif($type=='tender')
                $type=['tender', 'item'];
            else
                $type=[$type];

            $item->__tender_documents=array_where($item->documents, function($document, $key) use ($type, $item){
                return ($item->procurementMethodType=='' || (in_array($document->documentOf, $type) && (empty($item->lots) || (!empty($item->lots) && sizeof($item->lots)==1 || !isset($document->relatedItem)))));
            });

            usort($item->__tender_documents, function ($a, $b)
            {
                return intval(strtotime($b->dateModified))>intval(strtotime($a->dateModified));
            });

            $ids=[];

            foreach($item->__tender_documents as $document)
            {
                if(in_array($document->id, $ids))
                {
                    $document->stroked=new \StdClass();
                    $document->stroked=true;
                }

                $ids[]=$document->id;
            }

            $item->__tender_documents_stroked=sizeof(array_where($item->__tender_documents, function($document, $key){
                    return !empty($document->stroked);
                }))>0;
        }
    }

    private function get_uniqie_bids(&$item, $is_lot=false)
    {
        $item->__unique_bids=new \StdClass();
        $item->__unique_bids=null;

        if($is_lot)
            $bids=!empty($item->__bids)?$item->__bids:false;
        elseif(!empty($item->lots) && sizeof($item->lots)==1)
            $bids=!empty($item->bids)?$item->bids:false;
        else
            $bids=!empty($item->bids)?$item->bids:false;

        if(!empty($bids))
        {
            $bids=array_where($bids, function($bid, $key){
                return empty($bid->status) || !in_array($bid->status, ['deleted', 'invalid']);
            });

            $ids=[];

            foreach($bids as $award)
            {
                foreach($award->tenderers as $tenderer)
                {
                    array_push($ids, $tenderer->identifier->id);
                }
            }

            $ids=array_unique($ids);
            $item->__unique_bids=sizeof($ids);
        }
    }

    private function get_bids(&$item, $return=false)
    {
        if(!empty($item->bids))
        {
            if(in_array($item->status, ['active.pre-qualification', 'active.auction', 'active.pre-qualification.stand-still']))
            {
                if(!$return)
                    $item->__bids=null;
                else
                    return null;

            }
            elseif($item->procurementMethod=='open')
            {
                $item->__bids=new \StdClass();
                $item->__bids=[];

                if(!empty($item->bids))
                {
                    $bids=$item->bids;

                    foreach($bids as $k=>$bid)
                    {
                        if(!empty($bid->eligibilityDocuments))
                        {
                            if(empty($bid->documents))
                            {
                                $bid->documents=new \StdClass();
                                $bid->documents=[];
                            }

                            $bid->documents=array_merge($bid->documents, $bid->eligibilityDocuments);
                        }

                        if(!empty($bid->financialDocuments))
                        {
                            if(empty($bid->documents))
                            {
                                $bid->documents=new \StdClass();
                                $bid->documents=[];
                            }

                            $bid->documents=array_merge($bid->documents, $bid->financialDocuments);
                        }

                        if(!empty($bid->documents))
                        {
                            $bids[$k]->__documents_public=new \StdClass();
                            $bids[$k]->__documents_public=[];

                            $bids[$k]->__documents_confident=new \StdClass();
                            $bids[$k]->__documents_confident=[];

                            $bids[$k]->__documents_public=array_where($bid->documents, function($document, $key){
                                return empty($document->confidentiality) || $document->confidentiality!='buyerOnly';
                            });

                            $bids[$k]->__documents_confident=array_where($bid->documents, function($document, $key){
                                return !empty($document->confidentiality) && $document->confidentiality=='buyerOnly';
                            });

                            $this->parse_document_changes($bids[$k]->__documents_public);
                        }

                        if(!empty($item->awards))
                        {
                            foreach($item->awards as $award)
                            {
                                if(!empty($award->bid_id) && $award->bid_id==$bid->id)
                                {
                                    $bid->__award=$award;
                                }
                            }
                        }
                    }

                    if(!empty($bids))
                    {
                        $bids=array_where($bids, function($bid, $key){
                            return empty($bid->status) || !in_array($bid->status, ['deleted', 'invalid']);
                        });
                    }

                    if(!$return)
                        $item->__bids=$bids;
                    else
                        return $bids;
                }
                /*
                $lot->__tender_documents=array_where($item->documents, function($key, $document) use ($lot){
                    return $document->documentOf=='lot' && $document->relatedItem==$lot->id;
                });

                usort($lot->__tender_documents, function ($a, $b)
                {
                    return intval(strtotime($b->dateModified))>intval(strtotime($a->dateModified));
                });

                */

            }
        }
    }

    private function get_button_007(&$item, $procuringEntity)
    {
        $item->__button_007=false;

        if(($item->procurementMethod=='open' && in_array($item->procurementMethodType, ['aboveThresholdEU', 'aboveThresholdUA', 'aboveThresholdUA.defense', 'belowThreshold'])) || ($item->procurementMethod=='limited' && in_array($item->procurementMethodType, ['negotiation', 'negotiation.quick', 'reporting'])))
        {
            if(!empty($item->__documents))
            {
                $has_active_contracts=array_first($item->__documents, function($contract, $key){
                    return $contract->status=='active';
                });

                if($has_active_contracts && $item->status=='complete' && (!empty($item->__active_award->complaintPeriod->endDate) || !empty($item->__active_award->date)))
                {
                    $date=!empty($item->__active_award->complaintPeriod) ? date_create($item->__active_award->complaintPeriod->endDate) : date_create($item->__active_award->date);
                    $sub_days=0;

                    if(in_array($item->procurementMethodType, ['aboveThresholdUA', 'aboveThresholdEU', 'negotiation']))
                        $sub_days=10;

                    elseif(in_array($item->procurementMethodType, ['negotiation.quick']))
                        $sub_days=5;

                    elseif(in_array($item->procurementMethodType, ['aboveThresholdUA.defense']))
                        $sub_days=4;

                    elseif(in_array($item->procurementMethodType, ['belowThreshold']))
                        $sub_days=2;

                    elseif(in_array($item->procurementMethodType, ['reporting']))
                    {
                        $sub_days=0;
                        $date=date_create($item->__active_award->date);
                    }

                    $now=new DateTime();
                    $work_days=$this->parse_work_days();

                    for($i=0;$i<$sub_days;$i++)
                    {
                        $now->sub(new \DateInterval('P1D'));

                        if(in_array($now->format('Y-m-d'), $work_days))
                        {
                            $i--;
                            $sub_days++;
                        }
                    }

                    $date_from=date_format($date->sub(new \DateInterval('P'.$sub_days.'D')), 'Y.m.d');

                    $item->__button_007=(object) [
                        'edrpou'=>$procuringEntity->identifier->id,
                        'date_from'=>$date_from,
                        'partner'=>$item->__active_award->suppliers[0]->identifier->id,
                    ];
                }
            }
        }
    }

    private function get_qualifications(&$item, $return=false, $lot=false)
    {
        if(!empty($item->qualifications))
        {
            $__qualifications=[];

            $cnt=1;

            foreach($item->qualifications as $qualification)
            {
                $qualification->__name=new \StdClass();
                $qualification->__tenderers='';

                $qualification->__name=new \StdClass();
                $qualification->__name='';

                $qualification->__documents=new \StdClass();
                $qualification->__documents=[];

                $qualification->__bid_documents=new \StdClass();
                $qualification->__bid_documents=[];

                $qualification->__bid_documents_public=new \StdClass();
                $qualification->__bid_documents_public=[];

                $qualification->__bid_documents_confident=new \StdClass();
                $qualification->__bid_documents_confident=[];

                if(starts_with($item->status, 'active.pre-qualification') || starts_with($item->status, 'active.auction') || starts_with($item->status, 'active.pre-qualification.stand-still'))
                    $qualification->__name='Учасник '.$cnt;

                if(!empty($item->bids))
                {
                    $item->bids=array_where($item->bids, function($bid, $key){
                        return empty($bid->status) || !in_array($bid->status, ['deleted', 'invalid']);
                    });
                }

                $bid=array_first($item->bids, function($bid, $key) use ($qualification){
                    return $qualification->bidID==$bid->id;
                });

                if(!empty($qualification->documents))
                    $qualification->__documents=$qualification->documents;

                if(!empty($bid))
                {
                    $documents=!empty($bid->documents) ? $bid->documents : [];
                    $eligibilityDocuments=!empty($bid->eligibilityDocuments) ? $bid->eligibilityDocuments : [];

                    $documents=array_merge($documents, $eligibilityDocuments);

                    $financialDocuments=!empty($bid->financialDocuments) ? $bid->financialDocuments : [];

                    $documents=array_merge($documents, $financialDocuments);

                    if(!empty($bid->tenderers[0])) {
                        $qualification->__tenderers = $bid->tenderers[0];
                    }

                    if(!empty($bid->tenderers[0])) {
                        $qualification->__name = isset($bid->tenderers[0]->identifier->legalName) ? $bid->tenderers[0]->identifier->legalName : $bid->tenderers[0]->name;
                        $qualification->__tenderers = $bid->tenderers[0];
                    }

                    $qualification->__bid_documents=$documents;

                    $qualification->__bid_documents_public=array_where($qualification->__bid_documents, function($document, $key){
                        return empty($document->confidentiality) || $document->confidentiality!='buyerOnly';
                    });

                    $qualification->__bid_documents_confident=array_where($qualification->__bid_documents, function($document, $key){
                        return !empty($document->confidentiality) && $document->confidentiality=='buyerOnly';
                    });
                }

                array_push($__qualifications, $qualification);

                $cnt++;
            }

            if($item->procurementMethodType=='aboveThresholdEU')
            {
                $__qualifications=array_where($__qualifications, function($qualification, $key) use ($lot){
                    if($lot && $lot->status=='cancelled')
                        $out=!empty($qualification->lotID) && $lot->id==$qualification->lotID;
                    elseif($lot)
                        $out=$lot->id==$qualification->lotID && (empty($qualification->status) || !in_array($qualification->status, ['cancelled']));
                    else
                        $out=true;
                    //$out=empty($qualification->status) || !in_array($qualification->status, ['cancelled']);

                    return $out;
                });

            }

            if(!$return)
            {
                $item->__qualifications=new \StdClass();
                $item->__qualifications=$__qualifications;
            }
            else
                return $__qualifications;
        }
    }

    private function get_lots(&$item)
    {
        if(!empty($item->lots) && sizeof($item->lots)>1)
        {
            $statuses=Status::getStatuses('lot_status');

            /*
            usort($item->lots, function ($a, $b)
            {
                return strcmp($a->title, $b->title);
            });
            */

            $tender_bids=$this->get_bids($item, true);
            $parsed_lots=[];

            if($item->status=='cancelled')
            {
                foreach($item->lots as $lot)
                {
                    $lot->status='cancelled';

                    if(!empty($item->cancellations))
                    {
                        $lot->__cancellations=$item->cancellations;
                    }
                }
            }

            foreach($item->lots as $k=>$lot)
            {
                $lot=clone $lot;

                if(!empty($item->__eu_bids[$lot->id]))
                {
                    $lot->__eu_bids=new \StdClass();
                    $lot->__eu_bids=$item->__eu_bids[$lot->id];
                }

                $lot->procurementMethod=$item->procurementMethod;
                $lot->procurementMethodType=$item->procurementMethodType;

                if(!empty($item->__initial_bids_by_lot))
                {
                    if(!empty($item->__initial_bids_by_lot[$lot->id]))
                        $lot->__initial_bids=$item->__initial_bids_by_lot[$lot->id];
                }
                else
                    $lot->__initial_bids=$item->__initial_bids;

                if(!empty($item->__initial_bids_dates_by_lot))
                {
                    if(!empty($item->__initial_bids_dates_by_lot[$lot->id]))
                        $lot->__initial_bids_dates=$item->__initial_bids_dates_by_lot[$lot->id];
                }
                else
                    $lot->__initial_bids_dates=$item->__initial_bids_dates;

                $lot->__icon=new \StdClass();
                $lot->__icon=false;

                $lot->__items=new \StdClass();

                $lot->__items=array_where($item->items, function($it, $key) use ($lot){
                    return !empty($it->relatedLot) && $it->relatedLot==$lot->id;
                });

                $lot->__questions=new \StdClass();
                $lot->__questions=$this->get_questions_lots($item, $lot);

                $lot->__complaints_claims=new \StdClass();
                $lot->__complaints_claims=array_where($this->get_claims($item, 'lot', true), function($claim, $key) use ($lot){
                    return !empty($claim->relatedLot) && $claim->relatedLot==$lot->id;
                });

                $lot->__complaints_complaints=new \StdClass();
                $lot->__complaints_complaints=array_where($this->get_complaints($item, 'lot', true), function($complaint, $key) use ($lot){
                    return !empty($complaint->relatedLot) && $complaint->relatedLot==$lot->id;
                });

                if(!empty($item->documents))
                {
                    $lot->__tender_documents=new \StdClass();
                    $lot->__tender_documents=array_where($item->documents, function($document, $key) use ($lot){
                        return !empty($document->documentOf) && $document->documentOf=='lot' && $document->relatedItem==$lot->id;
                    });

                    if(isset($lot->__items)) {
                        foreach ($lot->__items AS $_item) {
                            $___documents = array_where($item->documents, function ($document, $key) use ($lot, $_item) {
                                return !empty($document->documentOf) && $document->documentOf == 'item' && $document->relatedItem == $_item->id;
                            });

                            if(is_array($___documents) && !empty($___documents)) {
                                $lot->__tender_documents = array_merge($lot->__tender_documents, $___documents);
                            }
                        }
                    }

                    usort($lot->__tender_documents, function ($a, $b)
                    {
                        return intval(strtotime($b->dateModified))>intval(strtotime($a->dateModified));
                    });

                    $ids=[];

                    foreach($lot->__tender_documents as $document)
                    {
                        if(in_array($document->id, $ids))
                        {
                            $document->stroked=new \StdClass();
                            $document->stroked=true;
                        }

                        $ids[]=$document->id;
                    }

                    $lot->__tender_documents_stroked=sizeof(array_where($lot->__tender_documents, function($document, $key){
                            return !empty($document->stroked);
                        }))>0;
                }

                $lot->awards=new \StdClass();
                $lot->awards=[];

                if(!empty($item->awards))
                {
                    $lot->awards=array_where($item->awards, function($award, $key) use ($lot){
                        return !empty($award->lotID) && $award->lotID==$lot->id;
                    });
                }

                if(!empty($item->features))
                {
                    $tender_features=array_where($item->features, function($feature, $key) use($lot, $item){
                        return $feature->featureOf=='tenderer' || ($feature->featureOf=='item' || ($feature->featureOf=='item' && !array_first($item->items, function($_item, $ikey) use($lot, $feature) {
                                return $feature->relatedItem == $_item->id && isset($_item->relatedLot) && $lot->id == $_item->relatedLot;
                            })));
                    });

                    $lot_features=array_where($item->features, function($feature, $key) use($lot, $item){
                        return ($feature->featureOf=='lot' && $feature->relatedItem==$lot->id) || ($feature->featureOf=='item' && array_first($item->items, function($_item, $ikey) use($lot, $feature) {
                                return $feature->relatedItem == $_item->id && isset($_item->relatedLot) && $lot->id == $_item->relatedLot;
                            }));
                    });
                }

                $features_price=1;

                if(!empty($tender_features))
                {
                    foreach($tender_features as $k=>$feature)
                    {
                        $max=0;

                        foreach($feature->enum as $one)
                            $max=max($max, floatval($one->value));

                        $tender_features[$k]->max = new \stdClass();
                        $tender_features[$k]->max=$max;

                        $features_price-=$max;

                        usort($feature->enum, function ($a, $b)
                        {
                            return strcmp($b->value, $a->value);
                        });

                        $tender_features[$k]->enum=$feature->enum;
                    }

                    $lot->__features=new \StdClass();
                    $lot->__features=$tender_features;
                }

                if(!empty($lot_features))
                {
                    foreach($lot_features as $k=>$feature)
                    {
                        $max=0;

                        foreach($feature->enum as $one)
                            $max=max($max, floatval($one->value));

                        $lot_features[$k]->max = new \stdClass();
                        $lot_features[$k]->max=$max;

                        $features_price-=$max;

                        usort($feature->enum, function ($a, $b)
                        {
                            return strcmp($b->value, $a->value);
                        });

                        $lot_features[$k]->enum=$feature->enum;
                    }

                    $lot->__lot_features=new \StdClass();
                    $lot->__lot_features=$lot_features;
                }

                $lot->__features_price=new \StdClass();
                $lot->__features_price=$features_price;

                if(!empty($tender_bids))
                {
                    $bids=array_where($tender_bids, function($bid, $key) use ($lot){
                        return !empty($bid->lotValues) && (!empty(array_where($bid->lotValues, function($value, $k) use ($lot){
                            return $value->relatedLot==$lot->id;
                        })));
                    });
                }

                if(!empty($item->features))
                {
                    $features_by_lot=array_where($item->features, function($feature, $k) use ($lot){
                        return $feature->featureOf!='lot' || ($feature->featureOf=='lot' && $feature->relatedItem==$lot->id);
                    });
                }else
                    $features_by_lot=[];

                if(!empty($tender_bids))
                {
                    $lot->__bids=new \StdClass();
                    $lot->__bids=[];
                    $lot->bids_values=new \StdClass();
                    $lot->bids_values=[];

                    foreach($tender_bids as $bid)
                    {
                        $lot_bid=false;

                        if(!empty($bid->lotValues))
                        {
                            $lot_bid=array_where($bid->lotValues, function($value, $key) use ($lot) {
                                return $value->relatedLot===$lot->id;
                            });
                        }

                        if(!empty($lot_bid))
                        {
                            $bid_value=array_values($lot_bid)[0];

                            $bid->__featured_coef=new \StdClass();
                            $bid->__featured_price=new \StdClass();

                            if(!empty($bid->parameters))
                            {
                                $value=0;

                                foreach($features_by_lot as $feature)
                                {
                                    $param=array_first($bid->parameters, function($param, $k) use($feature){
                                        return $param->code==$feature->code;
                                    });

                                    if($param)
                                        $value+=$param->value;
                                }

                                $featured_coef=trim(number_format(1+$value/$lot->__features_price, 10, '.', ' '), '.0');

                                $bid->__featured_coef=$featured_coef;
                                $bid->__featured_price=str_replace('.00', '', number_format($bid_value->value->amount/$featured_coef, 2, '.', ' '));
                            }

                            $bid->value=new \StdClass();
                            $bid->value=clone $bid_value->value;

                            $cloned_bid=clone $bid;

                            $cloned_bid->__documents_public=!empty($cloned_bid->__documents_public) ? array_where($cloned_bid->__documents_public, function($document, $key) use ($lot){
                                return $document->documentOf=='tender' || (($document->documentOf=='lot' || $document->documentOf=='item') && $document->relatedItem==$lot->id);
                            }):[];

                            $cloned_bid->__documents_confident=!empty($cloned_bid->__documents_confident) ? array_where($cloned_bid->__documents_confident, function($document, $key) use ($lot){
                                return $document->documentOf=='tender' || (($document->documentOf=='lot' || $document->documentOf=='item') && $document->relatedItem==$lot->id);
                            }):[];

                            $lot->__bids[]=$cloned_bid;
                        }
                    }

                    foreach($lot->__bids as $__bid)
                        $__bid->__award=null;

                    if(!empty($item->awards))
                    {
                        foreach($lot->__bids as $__bid)
                        {
                            foreach($item->awards as $award)
                            {
                                if($award->bid_id==$__bid->id && $award->lotID==$lot->id)
                                    $__bid->__award=$award;
                            }
                        }
                    }

                    usort($lot->__bids, function ($a, $b)
                    {
                        return floatval($a->value->amount)<floatval($b->value->amount);
                    });
                }

                if(!empty($item->qualifications))
                {
                    $lot->__qualifications=new \StdClass();

                    $lot->__qualifications=array_where($this->get_qualifications($item, true, $lot), function($qualification, $key) use ($lot){
                        return !empty($qualification->lotID) && $qualification->lotID==$lot->id;
                    });
                }

                if(!empty($item->cancellations) && empty($lot->__cancellations))
                {
                    $lot->__cancellations=new \StdClass();

                    $lot->__cancellations=array_where($item->cancellations, function($cancellation, $key) use ($lot){
                        return $cancellation->cancellationOf=='lot' && $cancellation->relatedLot==$lot->id;
                    });
                }

                $lot->tenderID=$item->tenderID;

                $this->get_uniqie_awards($lot);
                $this->get_uniqie_bids($lot, true);
                $this->get_awards($lot);
                $this->get_contracts($lot, !empty($item->contracts) ? $item->contracts : false, $lot->id);
                $this->get_contracts_changes($lot, !empty($item->contracts) ? $item->contracts : false, $lot->id);
                $this->get_contracts_ongoing($lot, !empty($item->contracts) ? $item->contracts : false, $lot->id);
                $this->get_button_007($lot, $item->procuringEntity);
                $this->get_cancellations($item, 'lot', $lot);

                $lot->__status_name = isset($statuses[$lot->status]) ? $statuses[$lot->status] : $lot->status;

                $parsed_lots[]=$lot;
            }

            $item->lots=$parsed_lots;
        }
    }

    private function get_signed_contracts(&$item)
    {
        if(!empty($item->contracts))
        {
            $item->__signed_contracts=array_where($item->contracts, function($contract, $key){
                return !empty($contract->dateSigned);
            });
        }
    }

    private function get_contracts(&$item, $contracts=false, $lotID=false)
    {
        if(!empty($contracts))
        {
            $contracts_by_lotid=[];
            $documents=[];

            if(!empty($item->awards))
            {
                foreach($item->awards as $award)
                {
                    if(!empty($award->lotID))
                    {
                        $contracts_by_lotid[$award->lotID]=array_where($contracts, function($contract, $key) use($award){
                            return $contract->awardID==$award->id;
                        });
                    }
                }
            }

            if(!empty($lotID))
                $contracts=!empty($contracts_by_lotid[$lotID]) ? $contracts_by_lotid[$lotID] : [];

            $item->__contracts=new \StdClass();

            if(!empty($contracts))
            {
                $item->__contracts=array_where($contracts, function($contract, $key){
                    return !in_array($contract->status, ['cancelled']);
                });

                foreach($contracts as $contract)
                {
                    if(!empty($contract->documents))
                    {
                        foreach($contract->documents as $document)
                        {
                            if(!empty($contract->dateSigned))
                            {
                                $document->dateSigned=new \StdClass();
                                $document->dateSigned=$contract->dateSigned;
                            }

                            $document->status=new \StdClass();
                            $document->status=$contract->status;

                            $documents[]=$document;
                        }
                    }
                }
            }

            usort($documents, function ($a, $b)
            {
                $datea = new DateTime($a->datePublished);
                $dateb = new DateTime($b->datePublished);

                return $datea>$dateb;
            });

            $documents=array_where($documents, function($document, $key){
                return !in_array($document->status, ['cancelled']);
            });

            $item->__documents=$documents;
        }
    }

    private function get_contracts_ongoing(&$item, $contracts=false, $lotID=false)
    {
        if(!empty($contracts))
        {
            $contracts_by_lotid=[];
            $documents=[];

            if(!empty($item->awards))
            {
                foreach($item->awards as $award)
                {
                    if(!empty($award->lotID))
                    {
                        $contracts_by_lotid[$award->lotID]=array_where($contracts, function($contract, $key) use($award){
                            return $contract->awardID==$award->id;
                        });
                    }
                }
            }

            if(!empty($lotID))
                $contracts=!empty($contracts_by_lotid[$lotID]) ? $contracts_by_lotid[$lotID] : [];

            $__contract_active=array_first($contracts, function($contract, $key){
                return !empty($contract->status) && $contract->status=='active';
            });

            $item->__contract_ongoing=null;

            if($__contract_active)
            {
                $id=$__contract_active->id;
                $item->__contract_ongoing=$this->parse_contracts_json($id);
            }
        }
    }

    private function get_contracts_changes(&$item, $contracts=false, $lotID=false)
    {
        if(!empty($contracts))
        {
            $contracts_by_lotid=[];
            $documents=[];

            if(!empty($item->awards))
            {
                foreach($item->awards as $award)
                {
                    if(!empty($award->lotID))
                    {
                        $contracts_by_lotid[$award->lotID]=array_where($contracts, function($contract, $key) use($award){
                            return $contract->awardID==$award->id;
                        });
                    }
                }
            }

            if(!empty($lotID))
                $contracts=!empty($contracts_by_lotid[$lotID]) ? $contracts_by_lotid[$lotID] : [];

            $__contracts_active=array_first($contracts, function($contract, $key){
                return !empty($contract->status) && $contract->status=='active';
            });

            $item->__contracts_changes=null;

            if($__contracts_active)
            {
                $id=$__contracts_active->id;
                $contracts=$this->parse_contracts_json($id);
                $rationale_types=$this->parse_rationale_type();

                if(!empty($contracts->changes))
                {
                    foreach($contracts->changes as $change)
                    {
                        $change->contract=array_where($contracts->documents, function($document, $key) use ($change){
                            return !empty($document->documentOf) && $document->documentOf=='change' && $document->relatedItem==$change->id;
                        });

                        foreach($change->rationaleTypes as $k=>$rationaleType)
                        {
                            $change->rationaleTypes[$k]=!empty($rationale_types->$rationaleType) ? $rationale_types->$rationaleType->title : $rationaleType;
                        }
                    }

                    $item->__contracts_changes=$contracts->changes;
                }
                //dd($item->__contracts_changes);
            }
        }
    }

    private function get_eu_lots(&$item)
    {
        if($item->procurementMethod=='open' && $item->procurementMethodType=='aboveThresholdEU')
        {
            if(!empty($item->bids))
            {
                if($item->__isMultiLot)
                {
                    $item->__eu_bids=new \StdClass();
                    $item->__eu_bids=[];

                    foreach($item->bids as $bid)
                    {
                        if(!empty($bid->documents))
                        {
                            $bid->__documents_public=new \StdClass();
                            $bid->__documents_public=[];

                            $bid->__documents_confident=new \StdClass();
                            $bid->__documents_confident=[];

                            $bid->__documents_public=array_where($bid->documents, function($document, $key){
                                return empty($document->confidentiality) || $document->confidentiality!='buyerOnly';
                            });

                            $bid->__documents_confident=array_where($bid->documents, function($document, $key){
                                return !empty($document->confidentiality) && $document->confidentiality=='buyerOnly';
                            });
                        }

                        $eu_bids=[];

                        if(!empty($item->qualifications))
                        {
                            $lots=array_where($item->qualifications, function($qualification, $key) use ($bid){
                                return $qualification->bidID==$bid->id;
                            });

                            foreach($lots as $lot)
                            {
                                if(empty($item->__eu_bids[$lot->lotID]))
                                    $item->__eu_bids[$lot->lotID]=[];

                                $item->__eu_bids[$lot->lotID][]=clone $bid;
                            }
                        }
                    }
                }
                else
                {
                    foreach($item->bids as $k=>$bid)
                    {
                        if(!empty($bid->documents))
                        {
                            $item->bids[$k]->__documents_public=new \StdClass();
                            $item->bids[$k]->__documents_public=[];

                            $item->bids[$k]->__documents_confident=new \StdClass();
                            $item->bids[$k]->__documents_confident=[];

                            $item->bids[$k]->__documents_public=array_where($bid->documents, function($document, $key){
                                return empty($document->confidentiality) || $document->confidentiality!='buyerOnly';
                            });

                            $item->bids[$k]->__documents_confident=array_where($bid->documents, function($document, $key){
                                return !empty($document->confidentiality) && $document->confidentiality=='buyerOnly';
                            });
                        }
                    }

                    $item->__eu_bids=$item->bids;
                }
            }
        }
    }

    private function get_cancellations(&$item, $type='tender', &$lot=null)
    {
        if($lot) {
            $lot->__cancellations=new \StdClass();
            $lot->__cancellations=null;

            if(!empty($item->cancellations))
            {
                $lot->__cancellations=array_where($item->cancellations, function($cancellation, $key) use ($type, $item, $lot){
                    return $cancellation->status == 'active' && $cancellation->cancellationOf==$type && $lot->id == $cancellation->relatedLot;
                });
            }
        } else {
            $item->__cancellations = new \StdClass();
            $item->__cancellations = null;

            if (!empty($item->cancellations)) {
                $item->__cancellations = array_where($item->cancellations, function ($cancellation, $key) use ($type, $item) {
                    return $cancellation->status == 'active' && $cancellation->cancellationOf == $type || (!empty($item->lots) && sizeof($item->lots) == 1 && $cancellation->cancellationOf == 'lot');
                });
            }
        }
    }

    private function get_yaml_documents(&$item)
    {
        if(!empty($item->documents))
        {
            $yaml_files=[];

            foreach($item->documents as $k=>$document)
            {
                if(pathinfo($document->title, PATHINFO_EXTENSION)=='yaml')
                {
                    array_push($yaml_files, $document);
                    unset($item->documents[$k]);
                }
            }

            if(!sizeof($item->documents))
                $item->documents=null;

            $item->__yaml_documents=new \StdClass();
            $item->__yaml_documents=$yaml_files;
        }
    }

    private function get_open_title(&$item)
    {
        $title=false;

        if($item->procurementMethod=='open' && $item->procurementMethodType=='aboveThresholdUA.defense')
            $title='hide';

        elseif($item->procurementMethod=='open' && in_array($item->procurementMethodType, ['competitiveDialogueUA']))
            $title=5;

        elseif($item->procurementMethod=='open' && in_array($item->procurementMethodType, ['competitiveDialogueEU']))
            $title=6;

        elseif($item->procurementMethod=='selective' && in_array($item->procurementMethodType, ['competitiveDialogueUA.stage2']))
            $title=7;

        elseif($item->procurementMethod=='selective' && in_array($item->procurementMethodType, ['competitiveDialogueEU.stage2']))
            $title=8;

        elseif($item->procurementMethod=='open' && $item->procurementMethodType!='belowThreshold')
            $title=1;

        elseif($item->procurementMethod=='open' && $item->procurementMethodType=='belowThreshold')
            $title=2;

        elseif($item->procurementMethod=='limited' && $item->procurementMethodType!='reporting')
            $title=3;

        elseif($item->procurementMethod=='limited' && $item->procurementMethodType=='reporting')
            $title=4;

        if($title=='hide')
        {
            $item->__open_name=new \StdClass();
            $item->__open_name='hide';
        }
        elseif($title)
        {
            $item->__open_name=new \StdClass();
            $item->__open_name=trans('tender.info_title.title'.$title);
        }
    }

    private function get_stage1TenderID(&$item)
    {
        if(!empty($item->dialogueID))
        {
            $stage1Tender=file_get_contents(env('API').'/tenders/'.$item->dialogueID);

            if(!empty($stage1Tender))
            {
                $stage1Tender=json_decode($stage1Tender);

                if(!empty($stage1Tender->data->tenderID))
                    $item->__stage1TenderID=$stage1Tender->data->tenderID;
            }
        }
    }

    private function get_stage2TenderID(&$item)
    {
        if(!empty($item->stage2TenderID))
        {
            $stage2Tender=file_get_contents(env('API').'/tenders/'.$item->stage2TenderID);

            if(!empty($stage2Tender))
            {
                $stage2Tender=json_decode($stage2Tender);

                if(!empty($stage2Tender->data->tenderID))
                    $item->__stage2TenderID=$stage2Tender->data->tenderID;
            }
        }
    }

    private function get_procedure(&$item)
    {
        if($item->procurementMethod=='open' && $item->procurementMethodType=='belowThreshold')
            $name='Допорогові закупівлі';

        if($item->procurementMethod=='open' && $item->procurementMethodType=='aboveThresholdUA')
            $name='Відкриті торги';

        if($item->procurementMethod=='open' && $item->procurementMethodType=='aboveThresholdEU')
            $name='Відкриті торги з публікацією англ.мовою';

        if($item->procurementMethod=='limited' && $item->procurementMethodType=='reporting')
            $name='Звіт про укладений договір';

        if($item->procurementMethod=='limited' && $item->procurementMethodType=='negotiation')
            $name='Переговорна процедура';

        if($item->procurementMethod=='limited' && $item->procurementMethodType=='negotiation.quick')
            $name='Переговорна процедура за нагальною потребою';

        if($item->procurementMethodType=='')
            $name='Без застосування електронної системи';

        if($item->procurementMethod=='open' && $item->procurementMethodType=='aboveThresholdUA.defense')
            $name='Переговорна процедура для потреб оборони';

        if($item->procurementMethod=='open' && $item->procurementMethodType=='competitiveDialogueUA')
            $name='Конкурентний діалог';

        if($item->procurementMethod=='open' && $item->procurementMethodType=='competitiveDialogueEU')
            $name='Конкурентний діалог з публікацією на англ. мові';

        if($item->procurementMethod=='selective' && $item->procurementMethodType=='competitiveDialogueUA.stage2')
            $name='Конкурентний діалог (2ий етап)';

        if($item->procurementMethod=='selective' && $item->procurementMethodType=='competitiveDialogueEU.stage2')
            $name='Конкурентний діалог з публікацією на англ. мові (2ий етап)';

        if(isset($name)) {
            $item->__procedure_name = new \StdClass();
            $item->__procedure_name = $name;
        }
    }

    private function parse_is_sign(&$item)
    {
        $item->__is_sign=new \StdClass();

        $is_sign=false;

        if(!empty($item->documents))
        {
            $is_sign=array_where($item->documents, function($document, $key){
                return $document->title=='sign.p7s';
            });
        }

        if($is_sign)
        {
            $url=head($is_sign)->url;
            $url=substr($url, 0, strpos($url, 'documents/')-1);

            $item->__sign_url=new \StdClass();
            $item->__sign_url=$url;
        }

        $item->__is_sign=!empty($is_sign);
    }

    private function parse_eu(&$item)
    {
        if(!empty($item->procurementMethod))
        {
            if($item->procurementMethod=='open' && $item->procurementMethodType=='aboveThresholdEU')
            {
                //if(in_array($item->status, ['active.pre-qualification', 'active.auction', 'active.pre-qualification.stand-still']))
                //    unset($item->bids);
            }
        }
    }

    private function sanitize_html($html)
    {
        $search = array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s');
        $replace = array('>', '<', '\\1');

        $html = preg_replace($search, $replace, $html);

        return $html;
    }

    private function parse_contracts_json($id)
    {
        return Cache::remember('contracts_'.$id, 15, function() use ($id)
        {
            $user_mode = $this->checkUserMode(Auth::getUser());

            $url=env('API_'.$user_mode.'CONTRACT').'/'.$id;

            try {
                $headers=get_headers($url);
            } catch (\Exception $e) {
                return false;
            }

            $contents=false;

            if(!empty($headers) && (int)substr($headers[0], 9, 3)==200)
                $contents=file_get_contents($url);

            return $contents ? json_decode($contents)->data : false;
        });
    }

    private function parse_rationale_type()
    {
        return Cache::remember('rationale_type', 60, function()
        {
            $contents=file_get_contents('http://standards.openprocurement.org/codelists/contract-change-rationale_type/uk.json');

            return $contents ? json_decode($contents) : false;
        });
    }

    private function parse_work_days()
    {
        return Cache::remember('work_days', 60, function()
        {
            $days=[];

            $off=file_get_contents('http://standards.openprocurement.org/calendar/workdays-off.json');
            $on=file_get_contents('http://standards.openprocurement.org/calendar/weekends-on.json');

            if($off)
                $days=array_merge($days, json_decode($off, true));

            if($on)
                $days=array_merge($days, json_decode($on, true));

            return $days;
        });
    }

    private function parse_document_changes(&$documents)
    {
        $ids=[];

        usort($documents, function ($a, $b)
        {
            $datea = new \DateTime($a->dateModified);
            $dateb = new \DateTime($b->dateModified);

            return $datea<$dateb;
        });

        foreach($documents as $document)
        {
            if(in_array($document->id, $ids))
            {
                $document->stroked=new \StdClass();
                $document->stroked=true;
            }

            $ids[]=$document->id;
        }
    }
}
