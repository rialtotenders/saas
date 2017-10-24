<?php

namespace Perevorot\Form\Classes;

use App;
use Carbon\Carbon;
use GuzzleHttp;
use October\Rain\Exception\ApplicationException;
use Perevorot\Rialtotender\Models\ChangeFile;
use Perevorot\Rialtotender\Models\Classifier;
use Perevorot\Rialtotender\Models\Contract;
use Perevorot\Rialtotender\Models\ContractFile;
use Perevorot\Rialtotender\Models\QualificationFile;
use Perevorot\Rialtotender\Models\Setting;
use Perevorot\Rialtotender\Models\TenderFile;
use Perevorot\Rialtotender\Traits\CurrentLocale;
use Perevorot\Users\Facades\Auth;
use Perevorot\Form\Classes\Parser;
use Perevorot\Rialtotender\Models\ApplicationFile;
use Perevorot\Rialtotender\Models\Application;
use Perevorot\Users\Models\User;
use Perevorot\Users\Traits\UserSetting;
use System\Models\File;
use Perevorot\Rialtotender\Models\ApiLog;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Mail;
use Event;

class Api
{
    use ApiHelpers, UserSetting, CurrentLocale;

    private $api_key;
    private $api_url;
    private $api_upload_key;
    private $api_upload_login;
    private $api_upload_url;

    private $client;
    private $jar;
    private $setting;
    private $user;
    private $user_mode;
    protected $cancel;

    var $debug = false;

    public function __construct($source = null, $env = false)
    {

        $this->client = new GuzzleHttp\Client();
        $this->jar = new GuzzleHttp\Cookie\CookieJar();

        $this->user = Auth::getUser();
        $this->user_mode = $this->checkUserMode($this->user, $source);
        $this->setting = Setting::instance();

        if(!$env) {

            if($this->user_mode == 'GOV_TEST_') {
                if($this->isGovTestVariablesFilled()){
                    throw new ApplicationException('При включенном режиме работы с государственными тендерами в тестовом режиме обязательны настройки: '.implode(', ', $this->isGovTestVariablesFilled()));
                }
            }

            $this->api_key = env('API_' . $this->user_mode . 'KEY');
            $this->api_url = env('API_' . $this->user_mode . 'URL');
            $this->api_upload_key = env('API_' . $this->user_mode . 'UPLOAD_KEY');
            $this->api_upload_login = env('API_' . $this->user_mode . 'UPLOAD_LOGIN');
            $this->api_upload_url = env('API_' . $this->user_mode . 'UPLOAD_URL');
        } else {
            $this->api_key = $env['API_' . $this->user_mode . 'KEY'];
            $this->api_url = $env['API_' . $this->user_mode . 'URL'];
            $this->api_upload_key = $env['API_' . $this->user_mode . 'UPLOAD_KEY'];
            $this->api_upload_login = $env['API_' . $this->user_mode . 'UPLOAD_LOGIN'];
            $this->api_upload_url = $env['API_' . $this->user_mode . 'UPLOAD_URL'];
        }

        if ($this->user) {
            $this->user->exportFieldsMutators();
        }
    }

    private function isGovTestVariablesFilled()
    {
        $variables=[
            'API_GOV_TEST_TENDER',
            'API_GOV_TEST_PLAN',
            'API_GOV_TEST_CONTRACT',
            'API_GOV_TEST_ORGSUGGEST',
            'API_GOV_TEST_KEY',
            'API_GOV_TEST_URL',
            'API_GOV_TEST_UPLOAD_KEY',
            'API_GOV_TEST_UPLOAD_LOGIN',
            'API_GOV_TEST_UPLOAD_URL'
        ];

        $empty=[];

        foreach($variables as $variable)
        {
            if(empty(env($variable, false)))
                array_push($empty, $variable);
        }

        return !empty($empty) ? $empty : false;
    }

    public function submitContract($tender, $user_contract, $post)
    {
        $url = $this->api_url . '/tenders/' . $tender->tender_system_id . '/contracts/'.$post['id'].'?acc_token='.$tender->token_id;
        $method = 'PATCH';

        $this->getCookies();

        $contract = new \stdClass();

        if($post['type'] == 1) {
            $complaintTime = Carbon::createFromTimestamp(strtotime($post['complaintPeriod']['endDate']))->addMinutes(1)->format('H:i:s');
            $post['dateSigned'] = Carbon::createFromFormat('d.m.Y H:i:s', ($post['dateSigned']." $complaintTime"))->toAtomString();
            $post['period']['startDate'] = Carbon::createFromFormat('d.m.Y', $post['period']['startDate'])->toAtomString();
            $post['period']['endDate'] = Carbon::createFromFormat('d.m.Y', $post['period']['endDate'])->toAtomString();

            $contract->awardID = $post['awardID'];
            $contract->title = $post['title'];
            $contract->dateSigned = $post['dateSigned'];
            $contract->contractNumber = $post['contractNumber'];
            $contract->period = (object)$post['period'];
        } else {
            $contract->awardID = $post['awardID'];
            $contract->status = 'active';
        }

        $data['data'] = $contract;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $response = (string)$response->getBody();
            $json = json_decode($response);

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            if(!isset($user_contract->tender_id)) {
                $user_contract->is_test = Auth::getUser()->is_test;
                $user_contract->user_id = Auth::getUser()->id;
                $user_contract->tender_id = $tender->tender_system_id;
                $user_contract->lot_id = $post['lot_id'];
            }

            $user_contract->contract_id = $post['id'];
            $user_contract->status = $json->data->status;
            $user_contract->title = $json->data->title;
            $user_contract->contract_number = $json->data->contractNumber;
            $user_contract->amount = $json->data->value->amount;
            $user_contract->cid = $json->data->contractID;
            $user_contract->date_signed = Carbon::createFromTimestamp(strtotime($json->data->dateSigned))->format('Y-m-d H:i:s');

            $this->uploadNewDocumentsForContract($user_contract, $tender, $post['id']);
            $this->uploadChangedDocumentsForContract($user_contract, $tender, $post['id']);

            $user_contract->save(null, post('_session_key'));

            return true;
        } catch (GuzzleHttp\Exception\ClientException $e) {

            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            //throw new \Exception('Ошибка: (' . $xRequestId . ') ' . $e->getMessage());
            return false;
        }
    }

    public function submitActiveContract($tender, $contract, $type = 1) {
        if(!$contract->token_id) {
            $this->getContractToken($tender, $contract);
        }

        $contract_json = $contract->getJson();

        if(isset($contract_json->change)) {
            if(!$this->sendChangesToContract($tender, $contract, $type)) {
                return false;
            }

            if($type == 2 && $contract->change_id) {
                if(!$this->sendChangesToContract($tender, $contract, $type)) {
                    return false;
                }

                Event::fire('perevorot.users.contract_changes', [
                    'contract' => $contract,
                ], true);
            }

            if(!$this->updateContract($tender, $contract)) {
                return false;
            }
        }

        return true;
    }

    public function updateContract($tender, $contract, $post = null)
    {
        if(!$contract->token_id) {
            $this->getContractToken($tender, $contract);
        }

        $url = $this->api_url . '/contracts/' . $contract->contract_id . '?acc_token=' . $contract->token_id;
        $method = 'PATCH';

        $this->getCookies();

        if($post !== null && !empty($post)) {
            if ($post['type'] == 1) {
                $_data['amountPaid']['amount'] = $post['amountPaid']['amount'];
            } elseif ($post['type'] == 2) {
                $_data['amountPaid']['amount'] = $post['amountPaid']['amount'];
                $_data['terminationDetails'] = $post['terminationDetails'];
            } elseif ($post['type'] == 3) {
                $_data = ['status' => 'terminated'];
            }
        } else {
            $contract_json = $contract->getJson();
            unset($contract_json->change);
            unset($contract_json->last_change);
            $_data = $contract_json;

            if(isset($contract_json->dateSigned)) {
                $_data->dateSigned = Carbon::createFromFormat('d.m.Y', $contract_json->dateSigned)->toAtomString();
            }
            $_data->period->startDate = Carbon::createFromFormat('d.m.Y', $contract_json->period->startDate)->toAtomString();
            $_data->period->endDate = Carbon::createFromFormat('d.m.Y', $contract_json->period->endDate)->toAtomString();
        }

        $data['data'] = (object)$_data;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data),
            ]);

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $response = (string)$response->getBody();
            $json = json_decode($response);

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            if(isset($json->data)) {

                if(isset($post['type'])) {

                    $contract->status = $json->data->status;
                    $contract->position = 1;

                    if($post['type'] != 3) {
                        $this->uploadNewDocumentsForActiveContract($contract, $tender);
                        $this->uploadChangedDocumentsForActiveContract($contract, $tender);
                    }
                }

                $contract->save(null, post('_session_key'));

                Event::fire('perevorot.users.contract', [
                    'contract' => $contract,
                ], true);

                return true;
            }

            return false;
        } catch (GuzzleHttp\Exception\ClientException $e) {

            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            //throw new \Exception('Ошибка: (' . $xRequestId . ') ' . $e->getMessage());
            return false;
        }
    }

    public function sendChangesToContract($tender, $contract, $type = 1) {

        //$_c = explode(' ', $contract->date_signed);
        $change = $contract->getJson()->change;

        if(!$contract->change_id) {
            $url = $this->api_url . '/contracts/' . $contract->contract_id . '/changes?acc_token=' . $contract->token_id;
            $method = 'POST';
        } else {
            $url = $this->api_url . '/contracts/' . $contract->contract_id . '/changes/'.$contract->change_id.'?acc_token=' . $contract->token_id;
            $method = 'PATCH';

            if($type == 2) {
                $change->status = 'active';
            }
        }

        $this->getCookies();
        //$change->dateSigned = Carbon::createFromFormat('d.m.Y H:i:s', $change->dateSigned." ".end($_c))->toAtomString();
        $change->dateSigned = Carbon::createFromFormat('d.m.Y', $change->dateSigned)->addMinute(-1)->toAtomString();
        $change->rationaleTypes = (array)$change->rationaleTypes;

        $data['data'] = $change;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data),
            ]);

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $response = (string)$response->getBody();
            $json = json_decode($response);

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            if(isset($json->data) && $json->data->status == 'pending') {
                $contract->change_status = $json->data->status;
                $contract->change_id = $json->data->id;
            } elseif(isset($json->data) && $json->data->status == 'active') {
                $contract->change_status = null;
                $contract->change_id = null;
                $contract->last_change_signed = Carbon::createFromTimestamp(strtotime($json->data->dateSigned))->format('Y-m-d H:i:s');
                $json = $contract->getJson();
                unset($json->change);
                $contract->json = json_encode($json);
            }

            $this->uploadNewDocumentsForChange($contract, $tender);
            $this->uploadChangedDocumentsForChange($contract, $tender);

            $contract->save();

            return true;
        } catch (GuzzleHttp\Exception\ClientException $e) {

            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            //throw new \Exception('Ошибка: (' . $xRequestId . ') ' . $e->getMessage());
            return false;
        }
    }

    public function getContractToken($tender, $contract)
    {
        $url = $this->api_url . '/contracts/' . $contract->contract_id.'/credentials?acc_token='.$tender->token_id;
        $method = 'PATCH';

        $this->getCookies();

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
            ]);

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $response = (string)$response->getBody();
            $json = json_decode($response);

            ApiLog::saveData(__FUNCTION__, $method, $url, '', $xRequestId, $response, $tender);

            if(isset($json->access)) {
                $contract->token_id = $json->access->token;
                $contract->save();
            }

            return true;
        } catch (GuzzleHttp\Exception\ClientException $e) {

            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, null, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function getContract($tender, $contract)
    {
        $url = $this->api_url . '/contracts/' . $contract->contract_id;
        $method = 'GET';

        $this->getCookies();

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
            ]);

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $response = (string)$response->getBody();
            $json = json_decode($response);

            ApiLog::saveData(__FUNCTION__, $method, $url, '', $xRequestId, $response, $tender);

            return $json;
        } catch (GuzzleHttp\Exception\ClientException $e) {

            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, null, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function getTender($tender)
    {
        $url = $this->api_url . '/tenders/' . $tender->id;
        $method = 'GET';

        $this->getCookies();

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
            ]);

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            $response = (string)$response->getBody();
            $json = json_decode($response);

            ApiLog::saveData(__FUNCTION__, $method, $url, '', $xRequestId, $response, $tender);

            return $json->data->status == 'active.tendering';
        } catch (GuzzleHttp\Exception\ClientException $e) {

            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, null, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function submitQualification($tender, $qualification, $status)
    {
        $this->getCookies();

        if($status != 'cancelled') {
            $this->uploadNewDocumentsForQualification($qualification, $tender);
        }

        $method = 'PATCH';
        $url = $this->api_url . '/tenders' . "/{$tender->tender_system_id}/qualifications/{$qualification->qualification_id}?acc_token={$tender->token_id}";

        $_data = ['status' => $status];
        $data['data'] = (object)$_data;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $json = json_decode($response->getBody());
            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string)$response->getBody(), $tender);

            if (isset($json->data)) {
                //$this->uploadChangedDocumentsForContract($qualification, $tender);

                $qualification->status = $json->data->status;

                if($status != 'cancelled') {
                    $qualification->save(null, post('_session_key'));
                } else {
                    $qualification->save();
                }

                return true;
            }

            return false;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = (string)$e->getResponse()->getBody();
            $json = json_decode($response);
            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function submitAward($tender, $post, $status)
    {
        $this->getCookies();

        $award_id = $post['id'];

        //if ($status == 'active' || $status == 'cancelled') {
            $this->uploadDocumentsForAward($tender, $award_id);
        //}

        $method = 'PATCH';
        $url = $this->api_url . '/tenders' . "/{$tender->tender_system_id}/awards/$award_id?acc_token={$tender->token_id}";
        $_data = ['status' => $status];

        if (isset($post['title'])) {
            $_data['title'] = $post['title'];
            $_data['description'] = $post['description'];
        }

        $data['data'] = (object)$_data;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $json = json_decode($response->getBody());
            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string)$response->getBody(), $tender);

            if (isset($json->data)) {
                return true;
            }

            return false;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = (string)$e->getResponse()->getBody();
            $json = json_decode($response);
            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function activatingCancellingTender($tender, $request_id)
    {
        $this->getCookies();

        $method = 'PATCH';
        $url = $this->api_url . '/tenders' . "/{$tender->tender_system_id}/cancellations/$request_id?acc_token={$tender->token_id}";
        $data['data'] = (object)['status' => 'active'];

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $json = json_decode($response->getBody());
            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string)$response->getBody(), $tender);

            if (isset($json->data)) {
                return true;
            }

            return false;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = (string)$e->getResponse()->getBody();
            $json = json_decode($response);
            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function cancellingTender($tender, $reason, $lot_id = null)
    {
        if(!$tender->tender_id) {
            return false;
        }

        $this->getCookies();

        $method = 'POST';
        $url = $this->api_url . '/tenders' . "/{$tender->tender_system_id}/cancellations?acc_token={$tender->token_id}";
        $data['data'] = (object)['reason' => $reason];

        if($lot_id) {
            $data['data']->cancellationOf = 'lot';
            $data['data']->relatedLot = $lot_id;
        }

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $json = json_decode($response->getBody());

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string)$response->getBody(), $tender);

            if (isset($json->data)) {
                $this->uploadDocumentsForCancellingTender($tender, $json->data->id);
                return $this->activatingCancellingTender($tender, $json->data->id);
            }

            return false;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = (string)$e->getResponse()->getBody();
            $json = json_decode($response);
            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function createPlan($plan, $update_plan = false)
    {
        $this->getCookies();

        if (!$plan->token_id) {
            $update_plan = false;
        }

        $setting = Setting::instance();
        $method = $update_plan ? 'PATCH' : 'POST';
        $url = $this->api_url . '/plans' . ($update_plan ? ("/{$plan->plan_system_id}?acc_token={$plan->token_id}") : '');
        $user = Auth::getUser();
        $_plan = $plan->getJson();

        if (isset($_plan->items)) {
            $_plan->items = (array)$_plan->items;

            if (!empty($_plan->items)) {
                sort($_plan->items);
            }
        }

        $plan->json = json_encode($_plan);
        $plan->save();

        $_plan->classification->scheme = 'CPV';
        $_plan->classification->description = Classifier::getCpvByCode($_plan->classification->id);
        $_plan->budget->id = $_plan->classification->id;

        $user_data = [
            'name' => $user->company_name,
            'identifier' => (object)[
                'scheme' => 'MD-IDNO', // ask in chat by this value
                'id' => $user->username,
                'legalName' => $user->company_name,
            ]
        ];

        if(!isset($_plan->tender->tenderPeriod))
        {
            $_plan->tender->tenderPeriod = new \stdClass();
            $_plan->tender->tenderPeriod->startDate = Carbon::createFromFormat('Y-m-d H:i', ($_plan->budget->year."-".$_plan->month."-01 00:00:00"))->toAtomString();
        }

        $_plan->procuringEntity = (object)$user_data;

        if (isset($_plan->items) && !empty($_plan->items)) {
            foreach ($_plan->items AS $k => $item) {
                if (!isset($_plan->items[$k]->id) || !$_plan->items[$k]->id) {
                    unset($_plan->items[$k]->id);
                }

                $_plan->items[$k]->classification->scheme = 'CPV';
                $_plan->items[$k]->classification->description = Classifier::getCpvByCode($item->classification->id);
                $_plan->items[$k]->deliveryDate->startDate = Carbon::createFromFormat('d.m.Y', $item->deliveryDate->startDate)->toAtomString();
                $_plan->items[$k]->deliveryDate->endDate = Carbon::createFromFormat('d.m.Y', $item->deliveryDate->endDate)->toAtomString();

                if (isset($setting->value['additionalClassifications']) && $setting->value['additionalClassifications']) {
                    $_plan->items[$k]->additionalClassifications = new \stdClass();
                    $_plan->items[$k]->additionalClassifications = [$_plan->items[$k]->classification];
                }
            }
        }

        if (!isset($_plan->tender->procurementMethod)) {
            $_plan->tender->procurementMethod = new \stdClass();
            $_plan->tender->procurementMethod = 'open';
        }
        if (!isset($_plan->tender->procurementMethodType)) {
            $_plan->tender->procurementMethodType = new \stdClass();
            $_plan->tender->procurementMethodType = 'belowThreshold';
        }

        unset($_plan->month);
        $data['data'] = $_plan;

        if ($this->user->is_test) {
            $data['data']->mode = 'test';
        }

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $json = json_decode($response->getBody());
            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string) $response->getBody(), ($update_plan ? $plan : $json->data));

            if ($update_plan && isset($json->data) || (!$update_plan && isset($json->access))) {

                if (!$update_plan) {
                    $plan->token_id = $json->access->token;
                    $plan->plan_id = $json->data->planID;
                    $plan->plan_system_id = $json->data->id;
                    $plan->save();
                }

                return true;
            }

            return false;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = (string)$e->getResponse()->getBody();
            $json = json_decode($response);
            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, ($update_plan ? $plan : null));

            //throw new \Exception($e->getMessage());
            return false;
        }
    }

    public function updateLot($tender, $lot)
    {
        $this->getCookies();

        $method = 'PATCH';
        $url = $this->api_url . '/tenders/' . $tender->tender_system_id . '/lots/'.$lot->id.'?acc_token=' . $tender->token_id;

        $_lot = new \stdClass();
        $_lot->title = $lot->title;
        $_lot->description = $lot->description;
        $_lot->value = $lot->value;
        $_lot->minimalStep = $lot->minimalStep;

        if($this->setting->checkAccess('guarantee')) {
            $_lot->guarantee = $lot->guarantee;
        }

        $data['data'] = $_lot;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string)$response->getBody(), $tender);

            return true;

        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = (string)$e->getResponse()->getBody();
            $json = json_decode($response);
            $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel == 'tender') {
                $this->cancellingTender($tender, __FUNCTION__ . ' error: (' . $xRequestId . ') ');
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function addLotToTender($tender)
    {
        $this->getCookies();

        $method = 'POST';
        $url = $this->api_url . '/tenders/' . $tender->tender_system_id . '/lots?acc_token=' . $tender->token_id;
        $_tender = $tender->getJson();

        foreach ($_tender->lots AS $lot) {

            $_lot = new \stdClass();
            $_lot->title = $lot->title;
            $_lot->description = $lot->description;
            $_lot->minimalStep = $lot->minimalStep;

            if(isset($lot->value)) {
                $_lot->value = $lot->value;
            } else {
                $_lot->value = new \stdClass();
                $_lot->value->amount = 999999999;
            }

            if($this->setting->checkAccess('guarantee')) {
                $_lot->guarantee = $lot->guarantee;
            }

            if(isset($lot->id) && $lot->id) {

                if($_tender->criteria && !empty($lot->features)) {
                    foreach($lot->features AS $fk => $feature) {
                        $feature->relatedItem = $lot->id;
                    }
                }

                $this->updateLot($tender, $lot);
                continue;
            }

            $data['data'] = $_lot;

            try {
                $response = $this->client->request($method, $url, [
                    'auth' => [
                        $this->api_key,
                        ''
                    ],
                    'headers' => [
                        'X-Client-Request-ID' => 'integer-dev',
                        'Content-Type' => 'application/json'
                    ],
                    'cookies' => $this->jar,
                    'body' => json_encode($data)
                ]);

                $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

                ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string)$response->getBody(), $tender);

                $response = (string)$response->getBody();
                $json = json_decode($response);
                $lot->id = $json->data->id;

                if($_tender->criteria && !empty($lot->features)) {
                    foreach($lot->features AS $fk => $feature) {
                        $feature->relatedItem = $lot->id;
                    }

                    $this->updateLot($tender, $lot);
                }

            } catch (GuzzleHttp\Exception\ClientException $e) {
                $response = (string)$e->getResponse()->getBody();
                $json = json_decode($response);
                $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

                ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

                if($this->cancel == 'tender') {
                    $this->cancellingTender($tender, __FUNCTION__ . ' error: (' . $xRequestId . ') ');
                }

                throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
            }
        }

        $tender->json = json_encode($_tender);
        $tender->save();

        $this->updateTender($tender);

        return true;
    }

    public function updateTender($tender, $status = false) {
        $this->getCookies();

        $method = 'PATCH';
        $url = $this->api_url . '/tenders/' . $tender->tender_system_id . '?acc_token=' . $tender->token_id;

        if($status) {
            $data['data']['status'] = $status;
        } else {
            $_tender = $tender->getJson();
            $items = [];

            if (isset($_tender->features) && !empty($_tender->features)) {
                $features = $_tender->features;
            } else {
                $features = [];
            }

            foreach ($_tender->lots AS $lot) {

                if (isset($lot->features) && !empty($lot->features)) {
                    $features = array_merge($features, $lot->features);
                }

                foreach ($lot->items AS $item) {
                    if ($lot->id && $item->id) {
                        $items[] = ['id' => $item->id, 'relatedLot' => $lot->id];
                    }
                }
            }

            if ($_tender->criteria && !empty($features)) {
                foreach ($features AS $fk => $feature) {
                    foreach ($feature->enum AS $ek => $enum) {
                        if ($enum->value > 0) {
                            $features[$fk]->enum[$ek]->value = round($enum->value / 100, 2);
                        } elseif ($enum->value == 0 && $enum->title) {
                            $features[$fk]->enum[$ek]->value = 0;
                        }
                    }
                }
            }

            if (!empty($features)) {
                $data['data']['features'] = $features;
            }

            $data['data']['items'] = $items;
        }

            try {
                $response = $this->client->request($method, $url, [
                    'auth' => [
                        $this->api_key,
                        ''
                    ],
                    'headers' => [
                        'X-Client-Request-ID' => 'integer-dev',
                        'Content-Type' => 'application/json'
                    ],
                    'cookies' => $this->jar,
                    'body' => json_encode($data)
                ]);

                $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

                ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string)$response->getBody(), $tender);

                return json_decode($response->getBody());

            } catch (GuzzleHttp\Exception\ClientException $e) {
                $response = (string)$e->getResponse()->getBody();
                $json = json_decode($response);
                $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

                ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

                if($this->cancel == 'tender') {
                    $this->cancellingTender($tender, __FUNCTION__ . ' error: (' . $xRequestId . ') ');
                }

                throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
            }
    }

    public function createTender($tender, $update_tender = false, $_tender_files = [], $_lot_files = [])
    {
        $this->getCookies();

        if(!$tender->token_id) {
            $update_tender = false;
        }

        $method = $update_tender ? 'PATCH' : 'POST';
        $url = $this->api_url.'/tenders' . ($update_tender ? ("/{$tender->tender_system_id}?acc_token={$tender->token_id}") : '');
        $_tender = $tender->getJson();

        if(!$update_tender) {
            $this->cancel = 'tender';
        }

        if(!$_tender->lot) {
            sort($_tender->items);
            $tender->json = json_encode($_tender);
            $tender->save();
        }

        if($tender->is_empty_price) {
            $_tender->value->amount = 999999999;
        }

        if(!isset($_tender->procurementMethod)) {
            $_tender->procurementMethod = new \stdClass();
            $_tender->procurementMethod = 'open';
        }
        if(!isset($_tender->procurementMethodType)) {
            $_tender->procurementMethodType = new \stdClass();
            $_tender->procurementMethodType = 'belowThreshold';
        }

        if($_tender->procurementMethodType != 'aboveThresholdTS') {
            if (isset($_tender->enquiryPeriod->endDate)) {
                $tender->q_end_date = Carbon::createFromFormat('d.m.Y H:i', $_tender->enquiryPeriod->endDate)->format('Y-m-d H:i:s');
                $_tender->enquiryPeriod->startDate = Carbon::now()->toAtomString();
                $_tender->enquiryPeriod->endDate = Carbon::createFromFormat('d.m.Y H:i', $_tender->enquiryPeriod->endDate)->toAtomString();
            }

            if (isset($_tender->tenderPeriod->startDate)) {
                $_tender->tenderPeriod->startDate = Carbon::createFromFormat('d.m.Y H:i', $_tender->tenderPeriod->startDate)->toAtomString();
            }
        } elseif(isset($_tender->enquiryPeriod)) {
            unset($_tender->enquiryPeriod);
        }

        $_tender->tenderPeriod->endDate = Carbon::createFromFormat('d.m.Y H:i', $_tender->tenderPeriod->endDate)->toAtomString();

        $_tender->procuringEntity->kind = $this->user->is_go ? 'other' : 'general';
        $_tender->procuringEntity->name = $this->user->company_name;
        $_tender->procuringEntity->identifier = new \stdClass();
        $_tender->procuringEntity->identifier->id = $this->user->username;
        $_tender->procuringEntity->identifier->scheme = 'MD-IDNO';
        $_tender->procuringEntity->identifier->legalName = $this->user->company_name;
        $_tender->procuringEntity->address = new \stdClass();
        $_tender->procuringEntity->address->streetAddress = $this->user->company_address;
        $_tender->procuringEntity->address->postalCode = $this->user->company_index;
        $_tender->procuringEntity->address->locality = $this->user->company_city;
        $_tender->procuringEntity->address->region = $this->user->company_region;
        $_tender->procuringEntity->address->countryName = $this->user->company_country;

        if(!$_tender->lot) {
            foreach ($_tender->items AS $k => $item) {
                if (!isset($item->id) || !$item->id) {
                    unset($item->id);
                }

                if(!isset($item->classification->description)) {
                    $item->classification->description = Classifier::getCpvByCode($item->classification->id);
                }

                $item->classification->scheme = 'CPV';
                $item->deliveryAddress->countryName = $_tender->procuringEntity->address->countryName;
                $item->deliveryDate->startDate = Carbon::createFromFormat('d.m.Y H:i', $item->deliveryDate->startDate)->toAtomString();
                $item->deliveryDate->endDate = Carbon::createFromFormat('d.m.Y H:i', $item->deliveryDate->endDate)->toAtomString();
            }
        } else {
            foreach ($_tender->lots AS $lk => $lot) {
                if (!isset($lot->id) || !$lot->id) {
                    unset($lot->id);
                }

                foreach ($lot->items AS $ik => $item) {
                    if (!isset($item->id) || !$item->id) {
                        unset($item->id);
                    }

                    if(!isset($item->classification->description)) {
                        $item->classification->description = Classifier::getCpvByCode($item->classification->id);
                    }

                    $item->classification->scheme = 'CPV';
                    $item->deliveryAddress->countryName = $_tender->procuringEntity->address->countryName;
                    $item->deliveryDate->startDate = Carbon::createFromFormat('d.m.Y H:i', $item->deliveryDate->startDate)->toAtomString();
                    $item->deliveryDate->endDate = Carbon::createFromFormat('d.m.Y H:i', $item->deliveryDate->endDate)->toAtomString();
                }
            }
        }

        if($_tender->criteria && !empty($_tender->features)) {
            foreach($_tender->features AS $fk => $feature) {
                foreach($feature->enum AS $ek => $enum) {
                    if($enum->value > 0) {
                        $_tender->features[$fk]->enum[$ek]->value = round($enum->value / 100, 2);
                    } elseif($enum->value == 0 && $enum->title) {
                        $_tender->features[$fk]->enum[$ek]->value = 0;
                    }
                }
            }
        } else {
            unset($_tender->features);
        }

        if($_tender->lot) {
            $items = [];
            $__tender = $_tender;
            $sum = 0;
            $sum_step = 0;

            foreach($_tender->lots AS $lk => $lot) {

                if(@$lot->is_empty_price) {
                    $sum += 999999999;
                } else {
                    $sum += $lot->value->amount;
                }

                if(isset($lot->is_empty_price))
                    unset($lot->is_empty_price);

                $sum_step += $lot->minimalStep->amount;

                foreach($lot->items AS $item) {
                    $items[] = $item;
                }
            }

            $__tender->minimalStep = new \stdClass();
            $__tender->minimalStep->amount = ($sum_step / count($__tender->lots));
            $__tender->minimalStep->currency = $__tender->value->currency;
            $__tender->minimalStep->valueAddedTaxIncluded = false;
            $__tender->value->amount = $sum;
            $__tender->items = $items;

            if(isset($__tender->is_empty_price))
                unset($__tender->is_empty_price);

            unset($__tender->cpv);
            unset($__tender->criteria);
            unset($__tender->lot);
            unset($__tender->lots);
            unset($__tender->next_step);
            unset($__tender->step);

            $data['data'] = $__tender;
        } else {

            if(isset($_tender->is_empty_price))
                unset($_tender->is_empty_price);

            unset($_tender->cpv);
            unset($_tender->criteria);
            unset($_tender->lot);
            unset($_tender->lots);
            unset($_tender->next_step);
            unset($_tender->step);

            $data['data'] = $_tender;
        }

        if($this->user->is_accelerator && $this->user->is_test) {
            $data['data']->mode = 'test';
            $data['data']->procurementMethodDetails = 'quick, accelerator='.$this->setting->get_value('accelerator_time', 1440);
            $data['data']->submissionMethodDetails = 'quick';
        } elseif($this->user->is_test) {
            $data['data']->mode = 'test';
        }

        if(!$tender->tender_id && $_tender->procurementMethodType != 'aboveThresholdTS') {
            $data['data']->status = 'draft';
        }

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json',
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $json = json_decode($response->getBody());

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string) $response->getBody(), ($update_tender ? $tender : $json->data));

            if($update_tender && isset($json->data) || (!$update_tender && isset($json->access))) {

                $_tender = $tender->getJson();

                if(!$_tender->lot) {
                    foreach ($json->data->items AS $k => $item) {
                        $_tender->items[$k]->id = $item->id;
                    }
                } else {
                    foreach ($_tender->lots AS $lk => $lot) {
                        foreach ($lot->items AS $li => $item) {
                            $_item = array_shift($json->data->items);
                            $_tender->lots[$lk]->items[$li]->id = $_item->id;
                        }
                    }
                }

                $tender->json = json_encode($_tender);

                if(!$update_tender) {
                    $tender->token_id = $json->access->token;
                    $tender->tender_id = $json->data->tenderID;
                    $tender->tender_system_id = $json->data->id;
                    $tender->is_complete = 1;

                    if(isset($data['data']->mode)) {
                        $tender->is_test = 1;
                    }

                } else {
                    Event::fire('perevorot.rialtotender.tender_changed', [
                        'tender' => $tender,
                        'type' => 'updated',
                    ], true);
                }

                if($json->data->status == 'draft') {
                    $this->updateTender($tender, 'active.enquiries');
                }

                if($_tender->lot) {
                    if($this->addLotToTender($tender)) {

                        $_tender = $tender->getJson();

                        foreach($_tender->lots AS $lkey => $lot) {
                            if($lot->id && isset($_lot_files[$lkey])) {
                                $this->uploadNewDocumentsForTender($tender, $_lot_files[$lkey], $lot->id);
                            }
                        }
                    }
                }

                $this->uploadNewDocumentsForTender($tender, $_tender_files);
                $this->uploadChangedDocumentsForTender($tender);

                $tender->save();

                return true;
            }

            return false;
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);

            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, ($update_tender ? $tender : null));

            if($this->cancel == 'tender') {
                $this->cancellingTender($tender, __FUNCTION__ . ' error: (' . $xRequestId . ') ');
            }

            //throw new \Exception($e->getMessage());
            return false;
        }
    }

    public function sendAnswer($answer, $tender)
    {
        $this->getCookies();

        $method='PATCH';
        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/questions/'.$answer['qid']."?acc_token={$tender->token_id}";

        $data['data']['answer'] = $answer['answer'];

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string) $response->getBody(), $tender);

            $response=(string) $response->getBody();
            $json=json_decode($response);

            if(!empty($json->data)){
                return true;
            }
            else
            {
                return false;
            }
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);

            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function sendQuestion($question, $tender)
    {
        $this->getCookies();

        $method='POST';
        $url=$this->api_url.'/tenders/'.$question->tender_id.'/questions';

        $data['data']['author'] = $this->getTenderers();
        $data['data']['description'] = $question->question;
        $data['data']['title'] = $question->title;

        if($question->lot_id) {
            $data['data']['questionOf'] = 'lot';
            $data['data']['relatedItem'] = $question->lot_id;
        }

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, (string) $response->getBody(), $tender);

            if(!empty($response->getHeader('Location')))
            {
                return current($response->getHeader('Location'));
            }
            else
            {
                return false;
            }
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);

            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    public function getBidData(&$bid, $tender)
    {
        $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'?acc_token='.$bid->token_id;
        $method='GET';

        $this->getCookies();

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json'
                ],
                'cookies' => $this->jar,
            ]);

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, '', $xRequestId, (string) $response->getBody(), $tender);

            $response=(string) $response->getBody();
            $json=json_decode($response);

            return $json;
        }
        catch (GuzzleHttp\Exception\ClientException $e) {

            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, null, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }

        return false;
    }

    public function bidQualificationDocuments(&$bid, $tender, $_files = [], $related = false)
    {
        if($bid->id) {
            $this->getCookies();

            $this->uploadNewDocuments($bid, $tender, $_files, 'qualificationDocuments', $related);
            $this->uploadChangedDocuments($bid, $tender);

            return true;
        }

        return false;
    }

    public function bidToMultiLot($bids, $tender, $_files = [])
    {
        foreach($bids AS $bid) {
            $apps[] = Application::find($bid->application_id);
        }

        $bid = head($apps);

        if(!$bid->bid_id){
            //new bid
            $url=$this->api_url.'/tenders/'.$tender->id.'/bids';
            $method='POST';
        }else{
            //update bid
            $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'?acc_token='.$bid->token_id;
            $method='PATCH';
        }

        $this->getCookies();

        $data=[
            'data' => [
                'tenderers' => [
                    $this->getTenderers()
                ],
            ],
        ];

        if(!$bid->bid_id) {
            $data['data']['status'] = 'draft';
        }

        $_features = [];

        foreach($apps AS $k => $app) {

            $value = [
                'amount' => $app->feature_price ? $app->feature_price : $app->price,
                'valueAddedTaxIncluded' => $tender->value->valueAddedTaxIncluded,
                'currency' => $tender->value->currency
            ];

            $data['data']['lotValues'][] = [
                'value' => $value,
                'relatedLot' => $app->lot_id
            ];

            if (!$k && $app->tender_features) {
                $features = json_decode($app->tender_features);

                foreach ($features AS $code => $value) {
                    $_features[] = (object)['code' => $code, 'value' => $value];
                }
            }
            if ($app->lot_features) {
                $features = json_decode($app->lot_features);

                foreach ($features AS $code => $value) {
                    $_features[] = (object)['code' => $code, 'value' => $value];
                }
            }
        }

        if(isset($_features)) {
            $data['data']['parameters'] = $_features;
        }

            try {
                    $response = $this->client->request($method, $url, [
                        'auth' => [
                            $this->api_key,
                            ''
                        ],
                        'headers' => [
                            'X-Client-Request-ID' => 'integer-dev',
                            'Content-Type' => 'application/json'
                        ],
                        'cookies' => $this->jar,
                        'body' => json_encode($data)
                    ]);

                    $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
                    $response = (string)$response->getBody();
                    $json = json_decode($response);

                    if($method == 'POST') {
                        $this->cancel = 'bid';
                    }

                    ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

                    if (!empty($json->data) || $bid->bid_id) {
                        if(!$bid->bid_id) {
                            $bid->bid_id = $json->data->id;

                            if (!empty($json->access->transfer))
                                $bid->transfer_id = $json->access->transfer;

                            if (!empty($json->access->token))
                                $bid->token_id = $json->access->token;

                            if ($json->data->status == 'draft') {
                                $this->activateNewBid($bid, $tender);
                            }
                        }

                        foreach($apps AS $app) {

                            if(!$app->bid_id) {
                                $app->bid_id = $bid->bid_id;
                                $app->transfer_id = $bid->transfer_id;
                                $app->token_id = $bid->token_id;
                                $app->save();
                            } else {
                                $app->save();
                            }

                            if(isset($_files[$app->lot_id])) {
                                $this->uploadNewDocuments($bid, $tender, $_files[$app->lot_id], 'documents', $app->lot_id);
                                unset($_files[$app->lot_id]);
                            }
                        }
                    }

                if ($bid->bid_id) {
                    $this->uploadNewDocuments($bid, $tender, $_files);
                    $this->uploadChangedDocuments($bid, $tender);
                }

                return true;

            } catch (GuzzleHttp\Exception\ClientException $e) {
                $response = (string)$e->getResponse()->getBody();
                $json = json_decode($response);
                $xRequestId = !empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

                ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

                if($this->cancel=='bid') {
                    $this->declineSingleLot($bid, $tender);
                }

                //throw new \Exception('Ошибка: ('.$xRequestId.') '.$e->getMessage());
                return false;
            }
    }

    public function bidSingleLot(&$bid, $tender, $update_data = true)
    {
        if(!$bid->id){
            //new bid
            $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids';
            $method='POST';
        }else{
            //update bid
            $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'?acc_token='.$bid->token_id;
            $method='PATCH';
        }

        $this->getCookies();

        $data=[
            'data' => [
                'tenderers' => [
                    $this->getTenderers()
                ],
            ],
        ];

        if(!$bid->bid_id) {
            $data['data']['status'] = 'draft';
        }
        
        $value = [
                'amount' => $bid->feature_price ? $bid->feature_price : $bid->price,
                'valueAddedTaxIncluded' => $tender->value->valueAddedTaxIncluded,
                'currency' => $tender->value->currency
            ];
        
        if(!empty($tender->lots) && sizeof($tender->lots)==1){
            $data['data']['lotValues']=[[
                'value' => $value,
                'relatedLot' => head($tender->lots)->id
            ]];
        }else {
            $data['data']['value']=$value;
        }

        if ($bid->tender_features) {
            $features = json_decode($bid->tender_features);
            $_features = [];
            foreach($features AS $code => $value) {
                $_features[] = (object)['code' => $code, 'value' => $value];
            }
            $data['data']['parameters'] = $_features;
        }

        try{
            if($update_data) {
                $response = $this->client->request($method, $url, [
                    'auth' => [
                        $this->api_key,
                        ''
                    ],
                    'headers' => [
                        'X-Client-Request-ID' => 'integer-dev',
                        'Content-Type' => 'application/json'
                    ],
                    'cookies' => $this->jar,
                    'body' => json_encode($data)
                ]);

                if($method == 'POST') {
                    $this->cancel = 'bid';
                }

                $xRequestId = !empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
                $response = (string)$response->getBody();

                ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

                $json = json_decode($response);

                if (!empty($json->data)) {
                    $bid->bid_id = $json->data->id;

                    if (!empty($json->access->transfer))
                        $bid->transfer_id = $json->access->transfer;

                    if (!empty($json->access->token))
                        $bid->token_id = $json->access->token;

                    if ($json->data->status == 'draft') {
                        $this->activateNewBid($bid, $tender);
                    }
                }
            }

            if($bid->id) {
                $this->uploadNewDocuments($bid, $tender);
                $this->uploadChangedDocuments($bid, $tender);
            }

            return true;

        }
        catch (GuzzleHttp\Exception\ClientException $e) {            
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel=='bid') {
                $this->declineSingleLot($bid, $tender);
            }

            //throw new \Exception('Ошибка: ('.$xRequestId.') '.$e->getMessage());
            return false;
        }
        
        return false;
    }

    private function uploadChangedDocuments($bid, $tender)
    {
        $documents = $bid->bidDocuments;
        
        foreach($documents as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){
                $systemDocument=File::find($applicationFile->system_file_id);
                $newSystemDocument=File::find($applicationFile->change_system_file_id);

                if($this->debug){
                    dump('change: '.$systemDocument->file_name.'->'.$newSystemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument($bid, $newSystemDocument, $tender);
                $uploadJson=$this->uploadDocument($bid, $newSystemDocument, $registerJson->upload_url, $tender);

                $applicationFile->system_file_id=$applicationFile->change_system_file_id;
                $applicationFile->change_system_file_id=0;
                $applicationFile->filename=$newSystemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;                
                $applicationFile->json=$uploadJson;

                $json=$this->changeDocumentToBid($bid, $uploadJson, $applicationFile, $newSystemDocument, $tender);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }
                
                $newSystemDocument->sort_order=$systemDocument->sort_order;
                $newSystemDocument->save();
                
                $applicationFile->save();
                $systemDocument->delete();
            }
        }
    }

    private function uploadChangedDocumentsForTender($tender)
    {
        foreach($tender->tenderDocuments as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){
                $systemDocument=File::find($applicationFile->system_file_id);
                $newSystemDocument=File::find($applicationFile->change_system_file_id);

                if($this->debug){
                    dump('change: '.$systemDocument->file_name.'->'.$newSystemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $newSystemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $newSystemDocument, $registerJson->upload_url, $tender);

                $applicationFile->system_file_id=$applicationFile->change_system_file_id;
                $applicationFile->change_system_file_id=0;
                $applicationFile->filename=$newSystemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->changeDocumentToTender($uploadJson, $applicationFile, $newSystemDocument, $tender);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }

                $newSystemDocument->sort_order=$systemDocument->sort_order;
                $newSystemDocument->save();

                $applicationFile->save();
                $systemDocument->delete();
            }
        }
    }

    private function uploadChangedDocumentsForChange($contract, $tender)
    {
        foreach($contract->allChangeDocuments as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){
                $systemDocument=File::find($applicationFile->system_file_id);
                $newSystemDocument=File::find($applicationFile->change_system_file_id);

                if($this->debug){
                    dump('change: '.$systemDocument->file_name.'->'.$newSystemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $newSystemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $newSystemDocument, $registerJson->upload_url, $tender);

                $applicationFile->system_file_id=$applicationFile->change_system_file_id;
                $applicationFile->change_system_file_id=0;
                $applicationFile->filename=$newSystemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->changeDocumentToChange($uploadJson, $applicationFile, $newSystemDocument, $tender, $contract);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }

                $newSystemDocument->sort_order=$systemDocument->sort_order;
                $newSystemDocument->save();

                $applicationFile->save();
                $systemDocument->delete();
            }
        }

        return true;
    }

    private function uploadChangedDocumentsForContract($contract, $tender, $contract_id)
    {
        foreach($contract->allContractDocuments as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){
                $systemDocument=File::find($applicationFile->system_file_id);
                $newSystemDocument=File::find($applicationFile->change_system_file_id);

                if($this->debug){
                    dump('change: '.$systemDocument->file_name.'->'.$newSystemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $newSystemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $newSystemDocument, $registerJson->upload_url, $tender);

                $applicationFile->system_file_id=$applicationFile->change_system_file_id;
                $applicationFile->change_system_file_id=0;
                $applicationFile->filename=$newSystemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->changeDocumentToContract($uploadJson, $applicationFile, $newSystemDocument, $tender, $contract_id);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }

                $newSystemDocument->sort_order=$systemDocument->sort_order;
                $newSystemDocument->save();

                $applicationFile->save();
                $systemDocument->delete();
            }
        }

        return true;
    }

    private function uploadDocumentsForAward($tender, $request_id)
    {
        foreach($tender->awardDocuments as $systemDocument){

            if($systemDocument->user_id != $this->user->id) {
                continue;
            }

            if($this->debug){
                dump('new: '.$systemDocument->file_name);
            }

            $registerJson=$this->registerUploadDocument(null, $systemDocument, $tender);
            $uploadJson=$this->uploadDocument(null, $systemDocument, $registerJson->upload_url, $tender);
            $this->addDocumentToAward($uploadJson, $systemDocument, $tender, $request_id);
        }

        return true;
    }

    private function uploadDocumentsForCancellingTender($tender, $request_id)
    {
        foreach($tender->cancellingDocuments as $systemDocument){

            if($systemDocument->user_id != $this->user->id) {
                continue;
            }

                if($this->debug){
                    dump('new: '.$systemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $systemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $systemDocument, $registerJson->upload_url, $tender);
                $this->addDocumentToCancellingTender($uploadJson, $systemDocument, $tender, $request_id);
        }

        return true;
    }

    public function uploadChangedDocumentsForActiveContract($contract, $tender, $related = false)
    {
        if($related) {
            $this->getCookies();
            $documents = $contract->otherAllActiveContractDocuments()->withDeferred(post('_session_key'))->get();
        } else {
            $documents = $contract->allActiveContractDocuments()->withDeferred(post('_session_key'))->get();
        }

        foreach($documents as $applicationFile){
            if((int)$applicationFile->change_system_file_id>0){
                $systemDocument=File::find($applicationFile->system_file_id);
                $newSystemDocument=File::find($applicationFile->change_system_file_id);

                if($this->debug){
                    dump('change: '.$systemDocument->file_name.'->'.$newSystemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $newSystemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $newSystemDocument, $registerJson->upload_url, $tender);

                $applicationFile->system_file_id=$applicationFile->change_system_file_id;
                $applicationFile->change_system_file_id=0;
                $applicationFile->filename=$newSystemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->changeDocumentToActiveContract($uploadJson, $applicationFile, $newSystemDocument, $tender, $contract);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;

                    if($related) {
                        if(!$this->setDocumentToContract($uploadJson, $newSystemDocument, $tender, $contract, $json->data->id, $related)) {
                            $applicationFile->save();
                            return false;
                        }
                    }
                }

                $newSystemDocument->sort_order=$systemDocument->sort_order;
                $newSystemDocument->save();

                $applicationFile->save();
                $systemDocument->delete();
            }
        }

        return true;
    }

    public function uploadNewDocumentsForActiveContract($contract, $tender, $related = false)
    {
        if($related) {
            $this->getCookies();
            $documents = $contract->otherActiveContractDocuments()->withDeferred(post('_session_key'))->where('user_id', $this->user->id)->get();
        } else {
            $documents = $contract->activeContractDocuments()->withDeferred(post('_session_key'))->where('user_id', $this->user->id)->get();
        }

        foreach($documents as $systemDocument){
            if($systemDocument->user_id == $this->user->id && !ContractFile::where('system_file_id', '=', $systemDocument->id)->orWhere('change_system_file_id', '=', $systemDocument->id)->first()){
                if($this->debug){
                    dump('new: '.$systemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $systemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $systemDocument, $registerJson->upload_url, $tender);

                $applicationFile=new ContractFile();

                $applicationFile->contract_id=$contract->contract_id;
                $applicationFile->filename=$systemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->system_file_id=$systemDocument->id;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->addDocumentToActiveContract($uploadJson, $systemDocument, $tender, $contract);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;

                    if($related) {
                        if(!$this->setDocumentToContract($uploadJson, $systemDocument, $tender, $contract, $json->data->id, $related)) {
                            $applicationFile->save();
                            return false;
                        }
                    }
                } else {
                    $applicationFile->save();
                    return false;
                }

                $applicationFile->save();

            }
        }
        return true;
    }

    private function uploadNewDocumentsForChange($contract, $tender)
    {
        $documents=$contract->changeDocuments()->withDeferred(post('_session_key'))->where('user_id', $this->user->id)->get();

        foreach($documents as $systemDocument){
            if($systemDocument->user_id == $this->user->id && !ChangeFile::where('system_file_id', '=', $systemDocument->id)->orWhere('change_system_file_id', '=', $systemDocument->id)->first()){
                if($this->debug){
                    dump('new: '.$systemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $systemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $systemDocument, $registerJson->upload_url, $tender);

                $applicationFile=new ChangeFile();

                $applicationFile->change_id=$contract->change_id;
                $applicationFile->filename=$systemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->system_file_id=$systemDocument->id;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->addDocumentToActiveContract($uploadJson, $systemDocument, $tender, $contract);
                if(isset($json->data)) {
                    $this->setDocumentToChange($uploadJson, $systemDocument, $tender, $contract, $json->data->id);
                }

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }

                $applicationFile->save();

            }
        }
        return true;
    }

    private function uploadNewDocumentsForQualification($q, $tender)
    {
        $documents=$q->qualificationDocuments()->withDeferred(post('_session_key'))->where('user_id', $this->user->id)->get();

        foreach($documents as $systemDocument){
            if($systemDocument->user_id == $this->user->id && !QualificationFile::where('system_file_id', '=', $systemDocument->id)->orWhere('change_system_file_id', '=', $systemDocument->id)->first()){
                if($this->debug){
                    dump('new: '.$systemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $systemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $systemDocument, $registerJson->upload_url, $tender);

                $applicationFile=new QualificationFile();

                $applicationFile->qualification_id=$q->qualification_id;
                $applicationFile->filename=$systemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->system_file_id=$systemDocument->id;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->addDocumentToQualification($uploadJson, $systemDocument, $tender, $q->qualification_id);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }

                $applicationFile->save();

            }
        }
        return true;
    }

    private function uploadNewDocumentsForContract($contract, $tender, $contract_id)
    {
        $documents=$contract->contractDocuments()->withDeferred(post('_session_key'))->where('user_id', $this->user->id)->get();

        foreach($documents as $systemDocument){
            if($systemDocument->user_id == $this->user->id && !ContractFile::where('system_file_id', '=', $systemDocument->id)->orWhere('change_system_file_id', '=', $systemDocument->id)->first()){
                if($this->debug){
                    dump('new: '.$systemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $systemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $systemDocument, $registerJson->upload_url, $tender);

                $applicationFile=new ContractFile();

                $applicationFile->contract_id=$contract_id;
                $applicationFile->filename=$systemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->system_file_id=$systemDocument->id;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->addDocumentToContract($uploadJson, $systemDocument, $tender, $contract_id);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }

                $applicationFile->save();

            }
        }
            return true;
    }

    private function uploadNewDocumentsForTender($tender, $_files = [], $lot_id = null)
    {
        if(empty($_files)) {
            $documents = $tender->documents;//->withDeferred(post('_session_key'))->get();
        } else {
            $documents = $_files;
        }

        foreach($documents as $systemDocument){
            if($systemDocument->user_id == $this->user->id && !TenderFile::where('system_file_id', '=', $systemDocument->id)->orWhere('change_system_file_id', '=', $systemDocument->id)->first()){
                if($this->debug){
                    dump('new: '.$systemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument(null, $systemDocument, $tender);
                $uploadJson=$this->uploadDocument(null, $systemDocument, $registerJson->upload_url, $tender);

                $applicationFile=new TenderFile();

                $applicationFile->lot_id=$lot_id;
                $applicationFile->tender_id=$tender->tender_system_id;
                $applicationFile->filename=$systemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;
                $applicationFile->system_file_id=$systemDocument->id;
                $applicationFile->json=json_encode($uploadJson);

                $json=$this->addDocumentToTender($uploadJson, $systemDocument, $tender, $lot_id);

                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }

                $applicationFile->save();
            }
        }
    }


    private function uploadNewDocuments($bid, $tender, $_files = [], $column='documents', $related = null)
    {

        if(empty($_files)) {
            $documents = $bid->{$column}()->withDeferred(post('_session_key'))->where('user_id', $this->user->id)->get();
        } else {
            $documents = $_files;
        }

        foreach($documents as $systemDocument){
            if($systemDocument->user_id == $this->user->id && !ApplicationFile::where('system_file_id', '=', $systemDocument->id)->orWhere('change_system_file_id', '=', $systemDocument->id)->first()){
                if($this->debug){
                    dump('new: '.$systemDocument->file_name);
                }

                $registerJson=$this->registerUploadDocument($bid, $systemDocument, $tender);
                $uploadJson=$this->uploadDocument($bid, $systemDocument, $registerJson->upload_url, $tender);

                $applicationFile=new ApplicationFile();
                
                $applicationFile->bid_id=$bid->bid_id;
                $applicationFile->lot_id= $related;
                $applicationFile->filename=$systemDocument->getFilename();
                $applicationFile->hash=$registerJson->data->hash;
                $applicationFile->url=$registerJson->data->url;
                $applicationFile->upload_url=$registerJson->upload_url;                
                $applicationFile->system_file_id=$systemDocument->id;
                $applicationFile->json=$uploadJson;

                $json=$this->addDocumentToBid($bid, $uploadJson, $systemDocument, $tender, $related);
                if(!empty($json->data->id)) {
                    $applicationFile->document_id=$json->data->id;
                    $applicationFile->url=$json->data->url;
                }
        
                $applicationFile->save();
            }
        }
    }
        
    private function getCookies()
    {
        $this->client->request('GET', $this->api_url.'/tenders', [
            'cookies' => $this->jar
        ]);        
    }
    
    private function getTenderers()
    {
        return [
            'name' => $this->user->company_name,
            'address' => [
                'countryName' => $this->user->company_country,
                'region' => 'Киев',
                'locality' => $this->user->company_city,
                'streetAddress' => $this->user->company_address,
                'postalCode' => $this->user->company_index,
            ],
            'contactPoint' => [
                'email' => $this->user->contact_email,
                'telephone' => $this->user->contact_mobile_phone,
                'faxNumber' => $this->user->contact_office_phone,
                'name' => $this->user->contact_fio,
            ],
            'identifier' => [
                'scheme' => 'MD-IDNO',
                'id' => $this->user->username
            ]
        ];
    }
    
    private function parseDocumentId($json)
    {
        $document_id='';

        if(!empty($json['get_url'])){
            $url=parse_url($json['get_url'], PHP_URL_PATH);

            $document_id=last(explode('/', $url));
        }

        return $document_id;
    }
    
    private function getHash($filePath)
    {
        return md5_file($filePath);
    }

    public static function cleanAccToken($str)
    {
        return preg_replace('/acc_token\=\w{32}/', 'acc_token=...', $str);
    }

    public function getDocumentFromBid($bid, $tender, $url)
    {
        $method='GET';
        $url.='&acc_token='.$bid->token_id;

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json',
                ],
                'cookies' => $this->jar,
            ]);

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, [], $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, [], $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }
}
