<?php

namespace Perevorot\Rialtotender\Classes;

use App;
use GuzzleHttp;

class BBApi
{

    public $bb_owner_username;
    public $bb_repository;
    public $bb_app_pass;
    public $bb_api_url;
    public $ws_api_url;
    public $ws_api_key;
    private $client;
    public $ws_project_id;

    var $debug = false;

    public function __construct()
    {
        $this->client = new GuzzleHttp\Client();

        $this->bb_owner_username = env('BITBUCKET_OWNER_USERNAME');
        $this->bb_repository = env('BITBUCKET_REPOSITORY');
        $this->bb_username = env('BITBUCKET_USERNAME');
        $this->bb_app_pass = env('BITBUCKET_APP_PASSWORD');
        $this->bb_api_url = env('BITBUCKET_API_URL');
        $this->ws_api_url = env('WORKSECTION_API_URL');
        $this->ws_api_key = env('WORKSECTION_API_KEY');
        $this->ws_project_id = env('WORKSECTION_PROJECT_ID');
    }

    public function getWSTasks() {

        $project = '/project/'.$this->ws_project_id.'/';
        $hash = md5($project.'get_tasks'.$this->ws_api_key);
        $method = 'GET';
        $url = $this->ws_api_url . '?action=get_tasks&page='.$project.'&hash='.$hash;

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);

            $json = json_decode($response->getBody());

            if(!isset($json->status) || $json->status == 'error') {
                return false;
            } else {
                return $json;
            }

        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new \Exception(__FUNCTION__.' error: '.$e->getMessage());
        }
    }

    public function getDiffDevMaster($page = 1)
    {
        $method = 'GET';
        $url = $this->bb_api_url . '/repositories/'.$this->bb_owner_username.'/'.$this->bb_repository.'/commits/develop?exclude=master&page='.$page;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->bb_username,
                    $this->bb_app_pass,
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);

            $json = json_decode($response->getBody());

            if(isset($json->error) || !isset($json->values)) {
                return false;
            } else {
                return $json;
            }

        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new \Exception(__FUNCTION__.' error: '.$e->getMessage());
        }
    }

    public function getMergedPR($id = null, $commits = false)
    {
        $method = 'GET';
        $url = $this->bb_api_url . '/repositories/'.$this->bb_owner_username.'/'.$this->bb_repository.'/pullrequests'.($id ? ('/'.$id.($commits ? '/commits' : '')) : '?state=MERGED');

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->bb_username,
                    $this->bb_app_pass,
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);

            $json = json_decode($response->getBody());

            if(isset($json->error)) {
                return false;
            } else {
                return $json;
            }

        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new \Exception(__FUNCTION__.' error: '.$e->getMessage());
        }
    }

    public function getCommit($hash)
    {
        $method = 'GET';
        $url = $this->bb_api_url . '/repositories/'.$this->bb_owner_username.'/'.$this->bb_repository.'/commit/'.$hash;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => [
                    $this->bb_username,
                    $this->bb_app_pass,
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);

            $json = json_decode($response->getBody());

            if(isset($json->error)) {
                return false;
            } else {
                return $json;
            }

        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new \Exception(__FUNCTION__.' error: '.$e->getMessage());
        }
    }
}
