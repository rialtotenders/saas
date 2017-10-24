<?php namespace Perevorot\Form\Components;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Perevorot\Form\Classes\Api;
use Perevorot\Form\Classes\ApiOrgsuggest;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Rialtotender\Models\Category;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\Plan;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\Status;
use Perevorot\Rialtotender\Models\Tender;
use Perevorot\Rialtotender\Traits\AccessToTenders;
use Perevorot\Users\Facades\Auth;
use Cms\Classes\ComponentBase;
use Cms\Classes\Theme;
use DateInterval;
use DateTime;
use Perevorot\Users\Traits\UserSetting;
use Request;
use Config;
use Input;
use Cache;
use App;
use RainLab\Translate\Models\Locale;
use Illuminate\Http\RedirectResponse;
use Log;
use ApplicationException;

class SearchResult extends ComponentBase
{
    use AccessToTenders, UserSetting;

    private $user_mode;
    private $user;
    private $setting;
    private $source;

    public function componentDetails()
    {
        return [
            'name' => 'Результаты поиска',
            'description' => '',
            'icon'=>'icon-files-o',
        ];
    }

    var $search_type;

    public function init()
    {
        $this->setting = Setting::instance();
        $this->user = Auth::getUser();
        $this->source = $this->param('source');
        //$this->user_mode = $this->checkUserMode($this->user, $this->source == 'gov');
    }

    public function onRun()
    {
        if(empty(get())) {
            $request = head(\Symfony\Component\HttpFoundation\Request::createFromGlobals()->server);

            if (isset($request['HTTP_REFERER']) && stripos($request['HTTP_REFERER'], 'login') !== FALSE) {
                return redirect()->to($this->getCurrentLocale() . 'tender/search' . ((($this->user && ($this->user->checkGroup('supplier') || $this->user->is_gov)) && $this->setting->checkAccess('is_gov')) ? '/gov' : '') . "?tab_type=1&status=active.enquiries&status=active.tendering");
            }
        }

        if(!$this->setting->checkAccess('is_tender') && $this->source!='gov') {
            return redirect()->to($this->getCurrentLocale().'tender/search/gov'."?tab_type=1&status=active.enquiries&status=active.tendering");
        }elseif(!$this->setting->checkAccess('is_gov') && $this->source=='gov') {
            return redirect()->to($this->getCurrentLocale().'tender/search'."?tab_type=1&status=active.enquiries&status=active.tendering");
        }

        if(!empty($this->param('source')) && $this->source!='gov')
            return redirect()->to($this->getCurrentLocale().'tender/search'."?tab_type=1&status=active.enquiries&status=active.tendering");

        if($this->source=='gov' && !$this->setting->checkAccess('is_gov')) {
            return redirect()->to($this->getCurrentLocale().'tender/search'."?tab_type=1&status=active.enquiries&status=active.tendering");
        }

        if($this->user) {
            if (!$this->user->checkGroup('supplier') && $this->user->is_gov && $this->source != 'gov' && $this->setting->checkAccess('is_gov')) {
                return redirect()->to($this->getCurrentLocale() . 'tender/search/gov'."?tab_type=1&status=active.enquiries&status=active.tendering");
            } elseif (!$this->user->checkGroup('supplier') && !$this->user->is_gov && $this->source == 'gov' && $this->setting->checkAccess('is_tender')) {
                return redirect()->to($this->getCurrentLocale() . 'tender/search'."?tab_type=1&status=active.enquiries&status=active.tendering");
            }
        }

        if($this->setting->checkAccess('is_gov')  && $this->isGovVariablesFilled()){
            throw new ApplicationException('При включенном режиме работы с государственными тендерами обязательны настройки: '.implode(', ', $this->isGovVariablesFilled()));
        }

        $redirect = $this->redirectTo();

        if($redirect instanceof RedirectResponse)
        {
            return $redirect;
        }

        $parser = Parser::instance();
        $tenders = $this->user ? Tender::getData([
            'type' => (!empty(get('user_tenders_type')) ? get('user_tenders_type') : false),
            'gov' => $this->user->is_gov,
            'test' => $this->user->is_test,
            'user_id' => $this->user->id])
            : [];

        if(count($tenders)) {
            foreach ($tenders AS $k => $tender) {

                if(!$tender->is_complete || $tender->status) {
                    continue;
                }

                if ($tender->tender_id && $item = $parser->tender_parse($tender->tender_id, 'tid', $tender->is_test)) {

                    $tender->status = $item->status;

                    if ($item->status == 'active.enquiries' && isset($item->enquiryPeriod->endDate)) {
                        $tender->date = Carbon::createFromTimestamp(strtotime($item->enquiryPeriod->endDate));
                    } elseif ($item->status == 'active.tendering' && isset($item->tenderPeriod->endDate)) {
                        $tender->date = Carbon::createFromTimestamp(strtotime($item->tenderPeriod->endDate));
                    } elseif ($item->status == 'active.auction') {

                        if ($item->__isMultiLot) {
                            $tender->date = Carbon::createFromTimestamp(strtotime($item->__lotAuctionPeriod->startDate));
                        } elseif (isset($item->auctionPeriod->startDate)) {
                            $tender->date = Carbon::createFromTimestamp(strtotime($item->auctionPeriod->startDate));
                        } else {
                            $tender->date = null;
                        }

                    } else {
                        $tender->date = null;
                    }

                    $tender->empty_questions = 0;

                    if (in_array($tender->status, ['complete', 'cancelled', 'unsuccessful'])) {
                        $tender->is_closed = 1;
                    }

                } else {
                    $tender->status = null;
                    $tender->date = null;
                    $tender->empty_questions = 0;
                }

                $tender->save();
            }
        }

        $this->page['tenders'] = $tenders;
    }

    public function onRender()
    {
        if(in_array('tender', Request::segments())) {
            $this->search_type = 'tender';
        }
        elseif(in_array('plan', Request::segments())) {
            $this->search_type = 'plan';
        }

        list($query_array, $preselected_values)=$this->parse_search_query();

        if(empty(array_filter($query_array)))
            $query_array=post('query');

        $json=$this->getSearchResults($query_array, $this->source == 'gov');
        $data=json_decode($json);
        $plans = $this->user ? Plan::getData(['user_id' => $this->user->id, 'test' => $this->user->is_test, 'gov' => $this->user->is_gov]) : [];

        $templateData=[
            'settings' => $this->setting,
            'user_tenders_type' => !empty(get('user_tenders_type')) ? get('user_tenders_type') : false,
            'is_gov' => ($this->source=='gov'),
            'tab_type' => (!empty(get('tab_type')) ? get('tab_type') : false),
            'show_contracts' => $this->setting->checkAccess('contracts'),
            'is_tender' => $this->setting->checkAccess('is_tender'),
            'is_plan' => $this->setting->checkAccess('is_plan'),
            'is_gov_tender' => $this->setting->checkAccess('is_gov'),
            'is_gov_plan' => $this->setting->checkAccess('is_gov_plan'),
            'contracts' => ($this->setting->checkAccess('contracts') && $this->user) ? Contract::getData(['test' => $this->user->is_test, 'user_id' => $this->user->id]) : [],
            'banner' => $this->getBanner(get('group')),
            'selected_group' => get('group'),
            'selected_date_from' => get('date_from'),
            'selected_date_to' => get('date_to'),
            'categories' => Category::getData(),
            'plans' => $plans,
            'is_ajax'=>!empty(post()),
            'search_type' => $this->search_type,
            'query_array' => $query_array || !empty(post('query')),
            'locale_href' => (App::getLocale()!=Locale::getDefault()->code ? App::getLocale() : ''),
            'preselected_values' => json_encode($preselected_values, JSON_UNESCAPED_UNICODE),
            'buttons' => Config::get('prozorro.buttons.'.$this->search_type),
            'siteLocale' => $this->getCurrentLocale(),
            'CurrentLocale' => $this->getCurrentLocaleWithoutSlash(),
            'dataStatus' => Status::getStatuses('tender'),
            'contracts_status' => Status::getStatuses('contract'),
        ];

        if(!empty($data->items))
        {
            $callback=camel_case('prepare_'.$this->search_type);

            $templateData=array_merge($templateData, $this->$callback($data));
        }
        elseif(empty($data) || (property_exists($data, 'items') && is_array($data->items) && !sizeof($data->items)))
        {
            $templateData['noresults']=true;
        }
        elseif(!empty($data->error))
        {
            $templateData['error']=$data->error;
        }

        $templateData['search_type']=$this->search_type;

        if($this->user){
            $bids=Application::getData(['user_id' => $this->user->id, 'test' => $this->user->is_test]);

            //$tender_ids=array_pluck($bids, 'tender_id');
            //$tender_ids=array_merge($tender_ids, $tender_ids);

            $items = [];
            $data = null;

            foreach($bids as $bkey => $bid) {

                $query[0] = 'id=' . $bid->tender_id;
                $data = json_decode($this->getSearchResults($query, $bid->is_gov));

                if(!empty($data->items)) {
                    $items[] = head($data->items);
                } else {
                    continue;
                }

                array_walk($items, function ($item) use ($bid) {
                    if($item->id == $bid->tender_id) {
                        if(!isset($item->lots)) {
                            $item->__my_bid = $bid;
                        } else {
                            $lot_for_bid = array_first($item->lots, function($lot, $lkey) use($bid) {
                                return $bid->lot_id == $lot->id;
                            });

                            if(empty($item->__my_bid)) {
                                $item->__my_bid = $lot_for_bid ? $bid : null;
                            }
                        }
                    }
                });
            }

            if(isset($_GET['dump']) && getenv('APP_ENV')=='local') {
                dd($items, $bids);
            }

            $templateData['my_items']=$items;
        }

        return $this->renderPartial('form/results.htm', $templateData);
    }

    public function getSearchResults($query, $gos_tender = null, $limit = false)
    {
        if(env('API_PRETEND'))
            return file_get_contents('./storage/pretend/results.json');

        if(empty($query) && $this->search_type == 'tender') {
            return '';
        } elseif(empty($query) && $this->search_type == 'plan') {
            $query = [];
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $user_mode = $this->checkUserMode($this->user, $gos_tender);

        if(env('API_'.$user_mode.'LOGIN') && env('API_'.$user_mode.'PASSWORD')){
            curl_setopt($ch, CURLOPT_USERPWD, env('API_'.$user_mode.'LOGIN') . ":" . env('API_'.$user_mode.'PASSWORD'));
        }

        $ignore_default_query=false;

        foreach($query as $k=>$q)
        {
            if(starts_with($q, ['query']))
            {
                $pattern_tender=env('API_'.$user_mode.'PREFIX_TENDER') ? '/'.env('API_'.$user_mode.'PREFIX_TENDER').'20\d{2}\-\d{2}\-\d{2}\-\d{6}(\-([1-9a-z]))?/' : false;
                $pattern_plan=env('API_'.$user_mode.'PREFIX_PLAN') ? '/'.env('API_'.$user_mode.'PREFIX_PLAN').'20\d{2}\-\d{2}\-\d{2}\-\d{6}(\-([1-9a-z]))?/' : false;

                $value_plan=explode('=', $q)[1];
                $value_tender=explode('=', $q)[1];

                preg_match_all($pattern_plan, $value_plan, $planIds);
                preg_match_all($pattern_tender, $value_tender, $tenderIds);

                $value_plan=trim(preg_filter($pattern_plan, '', $value_plan));
                $value_tender=trim(preg_filter($pattern_tender, '', $value_tender));

                if(!empty($tenderIds[0]))
                {
                    $ignore_default_query=true;

                    foreach($tenderIds[0] as $tenderID)
                        array_push($query, 'tid_like='.$tenderID);

                    $query[$k]='query='.urlencode($value_tender);
                }
                elseif(!empty($planIds[0]))
                {
                    $ignore_default_query=true;

                    foreach($planIds[0] as $planID)
                        array_push($query, 'pid='.$planID);

                    $query[$k]='query='.urlencode($value_plan);
                }
            }
            elseif(starts_with($q, ['procedure_t', 'procedure_p']))
            {
                $url=explode('=', $q, 2);
                unset($query[$k]);
                foreach(explode(',', $url[1]) as $u)
                    $query[]='proc_type='.$u;
            }
            elseif(substr($q, 0, 4)=='cpv=')
            {
                $url=explode('=', $q, 2);
                $cpv=explode('-', $url[1]);

                $query[$k]='cpv_like='.rtrim($cpv[0], '0');
            }
            elseif(substr($q, 0, 5)=='date[' || substr($q, 0, 9)=='dateplan[')
            {
                if(strpos($q, 'dateplan')!==false)
                    $one_date=str_replace(['dateplan[', ']='], ['', '='], $q);
                else
                    $one_date=str_replace(['date[', ']='], ['', '='], $q);

                $one_date=preg_split('/(=|—)/', $one_date);

                if(sizeof($one_date)==3)
                    $query[$k]=$one_date[0].'_start='.$this->convert_date($one_date[1]).'&'.$one_date[0].'_end='.$this->convert_date($one_date[2], new DateInterval('P1D'));
                else
                    unset($query[$k]);
            }
            elseif(stripos($q, 'date_to') !== FALSE)
            {
                $date=explode('=', $q, 2);

                if($date[1]) {
                    $query[$k] = 'date_end=' . Carbon::createFromTimestamp(strtotime($date[1]))->format('Y-m-d');
                }
            }
            elseif(stripos($q, 'date_from') !== FALSE)
            {
                $date=explode('=', $q, 2);

                if($date[1]) {
                    $query[$k] = 'date_start=' . Carbon::createFromTimestamp(strtotime($date[1]))->format('Y-m-d');
                }
            }
            elseif (stripos($q, 'group=') !== FALSE)
            {
                $group_key = $k;
            }
            else
            {
                $url=explode('=', $q, 2);

                if(!empty($url[1]))
                        $query[$k]=$url[0].'='.str_replace([' '], ['+'], $url[1]);
                    else
                        unset($query[$k]);
            }
        }

        if(!$ignore_default_query){
            if(env('API_'.$user_mode.'QUERY'.($this->search_type=='plan'?'_PLAN':'_TENDER'))){
                $query[]=env('API_'.$user_mode.'QUERY'.($this->search_type=='plan'?'_PLAN':'_TENDER'));
            }
        }

        if(isset($group_key))
        {
            $item = explode('=', $query[$group_key]);

            unset($query[$group_key]);

            if(isset($item[1]) && $item[1])
            {
                $cats = Category::getData(['code' => $item[1]]);

                if(count($cats) > 0)
                {
                    $category = $cats[0];
                    $category->cpv = trim($category->cpv) . "\r\n";

                    foreach(explode("\n", $category->cpv) AS $cpv)
                    {
                        $query[] = 'cpv_like=' . trim($cpv);
                    }
                }
            }
        }

        $query[]='start='.Input::get('start');

        $limit = $limit ? $limit : Input::get('limit');

        if($limit) {
            $query[] = 'limit=' . $limit;
        }

        \IntegerLog::info('search.query', $query);

        $path=env('API_'.$user_mode.strtoupper($this->search_type)).'?'.implode('&', $query);

        if(isset($_GET['api']) && getenv('APP_ENV')=='local')
            dd($path);

		curl_setopt($ch, CURLOPT_URL, $path);

		$result=curl_exec($ch);

		curl_close($ch);

		return $result;
	}

    public function prepareTender($data)
    {
        $dataStatus=[];

        foreach($data->items as $k=>$item)
        {
            $item->__icon=new \StdClass();
            $item->__icon=starts_with($item->tenderID, 'ocds-random-ua')?'pen':'mouse';

            $data->items[$k]=$item;
        }

        return [
            'total'=>$data->total,
            'search_type'=>$this->search_type,
            'error'=>false,
            'dataStatus'=> Status::getStatuses('tender'),
            'start'=>((int) Input::get('start') + Config::get('prozorro.page_limit')),
            'items'=>$data->items,
            'page_limit'=>Config::get('prozorro.page_limit'),
            'siteLocale' => $this->getCurrentLocale()
        ];
    }

    private function preparePlan($data)
    {
	    foreach($data->items as $item)
            $this->plan_check_start_month($item);

		return [
			'total'=>$data->total,
			'search_type'=>$this->search_type,
			'error'=>false,
			'start'=> ((int) Input::get('start') + Config::get('prozorro.page_limit')),
			'items'=>$data->items,
            'siteLocale' => $this->getCurrentLocale()
        ];
    }

    public function plan_check_start_month(&$item)
    {
        $item->__is_first_month=false;

        if(!empty($item->tender->tenderPeriod->startDate))
        {
            $date = strtotime($item->tender->tenderPeriod->startDate);

            $item->__is_first_month=false;//date('j', $date)==1 ? Lang::get('months.'.date('n', $date)).', '.date('Y', $date) : false;
        }
    }

	private function convert_date($date, $add=false)
	{
		$out=new DateTime($date);

		if($add)
			$out->add($add);

		return $out->format('Y-m-d');
	}

    public function parse_search_query()
    {
        $preselected_values=[];
        $query_array=[];
        $query_string=trim(Request::server('QUERY_STRING'), '&');

        $result='';

        if($query_string)
        {
            $query_array=explode('&', urldecode($query_string));

            if(sizeof($query_array))
            {
                foreach($query_array as $item)
                {
                    $item=explode('=', $item);

                    if(empty($item[1]))
                       continue;

                    $source=$item[0];
                    $search_value=!empty($item[1]) ? $item[1] : null;

                    $value=$this->get_value($source, $search_value);

                    if($value)
                        $preselected_values[$source][$search_value]=$value;
                    else
                        $preselected_values[$source][]=$search_value;
                }
            }
        }

        return [$query_array, $preselected_values];
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

    public function getBanner($group)
    {
        if($group && !empty(env('DB_HOST_2')))
        {
            //$ar = DB::connection('cbd')->table('z_category_dashboard')->get();

            try {
                if ($category = DB::connection('cbd')->table('z_category_dashboard')->where('category', $group)->first()) {

                    $customers = DB::connection('cbd')->table('z_top5')->where('category', $group)->orderBy('total', 'desc')->take(5)->get();

                    if(count($customers) > 0)
                    {
                        $customers = ApiOrgsuggest::getCustomers($customers);
                    }

                    $params = get();

                    foreach($params as $key => $v) {
                        if($key == 'edrpou') {
                            unset($params[$key]);
                        } else {
                            $params[$key] = "$key=$v";
                        }
                    }

                    return ['category' => $category, 'customers' => $customers, 'uri' => implode('&', $params)];
                }
            } catch (\Exception $e) {

                Log::info("Error in getBanner($group): ".$e);

                return null;
            }
        }
    }

    private function isGovVariablesFilled()
    {
        $variables=[
            'API_GOV_TENDER',
            'API_GOV_PLAN',
            'API_GOV_CONTRACT',
            'API_GOV_ORGSUGGEST',
            'API_GOV_KEY',
            'API_GOV_URL',
            'API_GOV_UPLOAD_KEY',
            'API_GOV_UPLOAD_LOGIN',
            'API_GOV_UPLOAD_URL'
        ];

        $empty=[];

        foreach($variables as $variable)
        {
            if(empty(env($variable, false)))
                array_push($empty, $variable);
        }

        return !empty($empty) ? $empty : false;
    }
}
