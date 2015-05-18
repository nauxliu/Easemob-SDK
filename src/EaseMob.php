<?php namespace Naux\EaseMob;

use GuzzleHttp\Client;

class EaseMob
{
    private $client;

    private $client_id;

    private $client_secret;

    private $org_name;

    private $app_name;

    private $url;


    public function __construct($client_id, $client_secret, $org_name, $app_name, $server_url)
    {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->org_name      = $org_name;
        $this->app_name      = $app_name;
        $this->url = $server_url . '/' . $org_name . '/' . $app_name . '/';

        $this->client = new Client([
            'base_url'  =>  $this->url,
             'defaults'  =>  [
                 'headers'   =>  ['Authorization' => 'Bearer ' . $this->getToken()]
             ],
        ]);
    }


    public function userDetails($username)
    {
        return $this->client->get('users/'.$username);
    }

    public function chatRecord($ql = '', $cursor = '', $limit = 20)
    {
        $query = [];

        $query['ql'] = !empty($ql) ? $ql : "order+by+timestamp+desc";
        $query['cursor'] = $cursor;
        $query['limit'] = $limit;

        //delete empty query
        foreach($query as $key => $value){
            if(empty($value)){
                unset($query[$key]);
            }
        }

        return $this->client->get('chatmessages?' . http_build_query($query));
    }

    /**
     * 获取 Token
     */
    public function getToken()
    {
        $client = new Client([
            'base_url'  =>  $this->url,
        ]);

        $body['grant_type']    = "client_credentials";
        $body['client_id']     = $this->client_id;
        $body['client_secret'] = $this->client_secret;

        $res = $client->post('token', [ 'body' => json_encode($body) ]);

        //TODO: cache the token
        $result = $res->json();

        return $result['access_token'];
    }
}