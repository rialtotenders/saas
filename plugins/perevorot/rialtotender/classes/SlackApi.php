<?php

namespace Perevorot\Rialtotender\Classes;

use App;
use GuzzleHttp;

class SlackApi
{
    public $api_url;
    private $client;

    var $debug = false;

    public function __construct($slack_api = null)
    {
        $this->client = new GuzzleHttp\Client();
        $this->api_url = $slack_api ? $slack_api : env('SLACK_HOOKS_URL');
    }

    public function sendHook($title, $msg) {

        if(!$this->api_url) {
            return;
        }

        $method = 'POST';
        $url = $this->api_url;
        $json = new \stdClass();
        $json->attachments = new \stdClass();
        $json->attachments = [];
        $json->attachments[] = (object)[
            "color" => "danger",
            'pretext' => "*".$title."*",
            'fallback' => "*".$title."*",
            'text' => $msg,
            "mrkdwn_in" => [
                "pretext",
                "fallback"
            ],
        ];

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($json)
            ]);

            $resp = json_decode($response->getBody());
            //print_r($resp);exit;
            return;

        } catch (GuzzleHttp\Exception\ClientException $e) {
            //throw new \Exception(__FUNCTION__.' error: '.$e->getMessage());
            return;
        }
    }
}
