<?php
    
namespace Perevorot\Form\Classes;

use Perevorot\Rialtotender\Models\ApiLog;
use GuzzleHttp;

trait ApiHelpers
{
    public function declineSingleLot($bid, $tender)
    {
        $this->getCookies();

        $method='DELETE';
        $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'?acc_token='.$bid->token_id;

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                ],
                'cookies' => $this->jar
            ]);
            
            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, '', $xRequestId, (string) $response->getBody(), $tender);

            return true;
        }
        catch (GuzzleHttp\Exception\ClientException $e) {            
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, null, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }
    
    private function activateNewBid($bid, $tender)
    {
        $method='PATCH';
        $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'?acc_token='.$bid->token_id;
        $status = 'active';

        if($tender->procurementMethodType == 'aboveThresholdTS') {
            $status = 'pending';
        }

        $data=[
            'data' => [
                'status' => $status
            ],
        ];

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
        }
        catch (GuzzleHttp\Exception\ClientException $e) {            
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel=='bid') {
                $this->declineSingleLot($bid, $tender);
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
            //return false;
        }
    }
    
    private function changeDocument($file, $bid, $oldDocument, $tender)
    {
        $method='PUT';
        $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'/documents/'.$oldDocument->document_id.'?acc_token='.$bid->token_id;

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_key,
                    ''
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                ],
                'cookies' => $this->jar,
                'multipart' => [
                    [
                        'name'     => 'file',
                        'filename' => $file->getFileName(),
                        'contents' => fopen($file->getLocalPath(), 'r')
                    ]
                ]
            ]);

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            $response=(string) $response->getBody();
            $json=json_decode($response, true);

            ApiLog::saveData(__FUNCTION__, $method, $url, ['multipart' => [
                [
                    'name'     => 'file',
                    'filename' => $file->getFileName(),
                    'contents' => '...'
                ]
            ]], $xRequestId, $response, $tender);
            
            $oldDocument->filename=$file->getFilename();
            $oldDocument->hash=md5_file($oldDocument->getLocalPath());
            $oldDocument->json=$json;
            $oldDocument->save();
        }
        catch (GuzzleHttp\Exception\ClientException $e) {            
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $file->getFileName(), $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }
    
    private function uploadDocument($bid, $file, $url, $tender)
    {
        $method='POST';

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_upload_login,
                    $this->api_upload_key
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                ],
                'cookies' => $this->jar,
                'multipart' => [
                    [
                        'name'     => 'file',
                        'filename' => $file->getFileName(),
                        'contents' => fopen($file->getLocalPath(), 'r')
                    ]
                ]
            ]);   

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, ['multipart' => [
                [
                    'name'     => 'file',
                    'filename' => $file->getFileName(),
                    'contents' => '...'
                ]
            ]], $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {            
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $file->getFileName(), $xRequestId, $e, $tender);

            if($this->cancel == 'tender') {
                $this->cancellingTender($tender, __FUNCTION__.' error: ('.$xRequestId.') ');
            } elseif($this->cancel == 'bid') {
                $this->declineSingleLot($bid, $tender);
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }
    
    private function changeDocumentToBid($bid, $json, $bidDocument, $systemFile, $tender)
    {
        $method='PUT';
        $doc_point = 'documents';
        $confidentiality = false;
        $confidentiality_text = false;

        if($systemFile->doc_type == 2) {
            $doc_point = 'financial_documents';
        }

        $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'/'.$doc_point.'/'.$bidDocument->document_id.'?acc_token='.$bid->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'id' => $bidDocument->document_id,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

        if($systemFile->doc_cdb_type) {
            $data['data']['documentType'] = $systemFile->doc_cdb_type;
        }

        if($systemFile->conf_text) {
            $data['data']['confidentiality'] = 'buyerOnly';
            $data['data']['confidentialityRationale'] = $systemFile->conf_text;
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
            $response=(string) $response->getBody();
            
            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {            
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel == 'bid') {
                $this->declineSingleLot($bid, $tender);
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }
    
    private function addDocumentToBid($bid, $json, $systemFile, $tender, $related)
    {
        $method='POST';
        $doc_point = 'documents';

        if($systemFile->doc_type == 2) {
            $doc_point = 'financial_documents';
        }

        $url=$this->api_url.'/tenders/'.$bid->tender_id.'/bids/'.$bid->bid_id.'/'.$doc_point.'?acc_token='.$bid->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

        if($systemFile->doc_cdb_type) {
            $data['data']['documentType'] = $systemFile->doc_cdb_type;
        }

        if($systemFile->conf_text) {
            $data['data']['confidentiality'] = 'buyerOnly';
            $data['data']['confidentialityRationale'] = $systemFile->conf_text;
        }

        if($related) {
            $data['data']['documentOf'] = 'lot';
            $data['data']['relatedItem'] = $related;
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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel == 'bid') {
                $this->declineSingleLot($bid, $tender);
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function changeDocumentToTender($json, $document, $systemFile, $tender)
    {
        $method='PUT';

        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/documents/'.$document->document_id.'?acc_token='.$tender->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'id' => $document->document_id,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

        if($systemFile->doc_cdb_type) {
            $data['data']['documentType'] = $systemFile->doc_cdb_type;
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

            $response=(string) $response->getBody();
            
            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);
            
            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel == 'tender') {
                $this->cancellingTender($tender, __FUNCTION__ . ' error: (' . $xRequestId . ') ');
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function changeDocumentToChange($json, $document, $systemFile, $tender, $contract)
    {
        $method='PUT';
        $url=$this->api_url.'/contracts/'.$contract->contract_id.'/documents/'.$document->document_id.'?acc_token='.$contract->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'id' => $document->document_id,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function changeDocumentToContract($json, $document, $systemFile, $tender, $contract_id)
    {
        $method='PUT';
        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/contracts/'.$contract_id.'/documents/'.$document->document_id.'?acc_token='.$tender->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'id' => $document->document_id,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function setDocumentToChange($json, $systemFile, $tender, $contract, $doc_id)
    {
        $method='PATCH';
        $url=$this->api_url.'/contracts/'.$contract->contract_id.'/documents/'.$doc_id.'?acc_token='.$contract->token_id;

        $data=[
            'data' => [
                "documentOf" => "change",
                "relatedItem" => $contract->change_id
            ]
        ];

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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return true;
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function setDocumentToContract($json, $systemFile, $tender, $contract, $doc_id, $type = false)
    {
        $method='PATCH';
        $url=$this->api_url.'/contracts/'.$contract->contract_id.'/documents/'.$doc_id.'?acc_token='.$contract->token_id;

        if($type && !empty(post('type'))) {
            $types = post('type');

            if(isset($types[$systemFile->id])) {
                $_type = $types[$systemFile->id];
            }
        }

        $data=[
            'data' => [
                "documentOf" => "contract",
                "relatedItem" => $contract->contract_id,
            ]
        ];

        if(isset($_type)) {
            $data['data']['documentType'] = $_type;
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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return true;
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            //throw new \Exception('addDocumentToAward error: ('.$xRequestId.') '.$e->getMessage());
            return false;
        }
    }

    private function changeDocumentToActiveContract($json, $document, $systemFile, $tender, $contract)
    {
        $method='PUT';
        $url=$this->api_url.'/contracts/'.$contract->contract_id.'/documents/'.$document->document_id.'?acc_token='.$contract->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function addDocumentToActiveContract($json, $systemFile, $tender, $contract)
    {
        $method='POST';
        $url=$this->api_url.'/contracts/'.$contract->contract_id.'/documents?acc_token='.$contract->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function addDocumentToQualification($json, $systemFile, $tender, $id)
    {
        $method='POST';
        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/qualifications/'.$id.'/documents?acc_token='.$tender->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function addDocumentToContract($json, $systemFile, $tender, $id)
    {
        $method='POST';
        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/contracts/'.$id.'/documents?acc_token='.$tender->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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
            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);

            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function addDocumentToAward($json, $systemFile, $tender, $request_id)
    {
        $method='POST';
        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/awards/'.$request_id.'/documents?acc_token='.$tender->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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

            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);
            
            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function addDocumentToCancellingTender($json, $systemFile, $tender, $request_id)
    {
        $method='POST';

        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/cancellations/'.$request_id.'/documents?acc_token='.$tender->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

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

            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);
            
            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function addDocumentToTender($json, $systemFile, $tender, $lot_id)
    {
        $method='POST';

        $url=$this->api_url.'/tenders/'.$tender->tender_system_id.'/documents?acc_token='.$tender->token_id;

        $data=[
            'data' => [
                'url' => $json->data->url,
                'hash' => $json->data->hash,
                'title' => $systemFile->getFilename(),
                'format' => $json->data->format,
            ]
        ];

        if($systemFile->doc_cdb_type) {
            $data['data']['documentType'] = $systemFile->doc_cdb_type;
        }

        if($lot_id) {
            $data['data']['documentOf'] = 'lot';
            $data['data']['relatedItem'] = $lot_id;
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

            $response=(string) $response->getBody();
            
            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);
            
            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);
            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel == 'tender') {
                $this->cancellingTender($tender, __FUNCTION__ . ' error: (' . $xRequestId . ') ');
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }

    private function registerUploadDocument($bid, $document, $tender)
    {
        $method='POST';
        $url=$this->api_upload_url.'/register';

        $hash=$this->getHash($document->getLocalPath());

        $data=[
            'data' => [
                'hash' => 'md5:'.$hash
            ]
        ];

        try{
            $response=$this->client->request($method, $url, [
                'auth'=>[
                    $this->api_upload_login,
                    $this->api_upload_key
                ],
                'headers' => [
                    'X-Client-Request-ID' => 'integer-dev',
                    'Content-Type' => 'application/json',
                ],
                'cookies' => $this->jar,
                'body' => json_encode($data)
            ]);

            $xRequestId=!empty($response->getHeader('x-request-id')[0]) ? $response->getHeader('x-request-id')[0] : false;

            $response=(string) $response->getBody();

            ApiLog::saveData(__FUNCTION__, $method, $url, $data, $xRequestId, $response, $tender);
            
            return json_decode($response);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {            
            $response=(string) $e->getResponse()->getBody();
            $json=json_decode($response);

            $xRequestId=!empty($e->getResponse()->getHeader('x-request-id')[0]) ? $e->getResponse()->getHeader('x-request-id')[0] : false;

            ApiLog::saveDataError(__FUNCTION__, $method, $url, $data, $xRequestId, $e, $tender);

            if($this->cancel == 'tender') {
                $this->cancellingTender($tender, __FUNCTION__.' error: ('.$xRequestId.') ');
            } elseif($this->cancel == 'bid') {
                $this->declineSingleLot($bid, $tender);
            }

            throw new \Exception(__FUNCTION__.' error: ('.$xRequestId.') '.self::cleanAccToken($e->getMessage()));
        }
    }
}
