<?php namespace Naux\EaseMob;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class EaseMob
{
    private $client;

    private $client_id;

    private $client_secret;

    private $org_name;

    private $app_name;

    private $url;

    private $token;


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
     * @param string $from_user 发送者用户名
     * @param array $username array('1','2') 接收者
     * @param string $target_type 默认为：users。向用户发消息: users, 向群组发送消息: chatgroups
     * @param string $content 消息内容
     * @param array $ext 自定义扩展字段
     * @return \GuzzleHttp\Message\ResponseInterface
     * 
     * @note 群发的话，你可以在 $username 数组里面最多写20个用户的名字， 同一个IP每秒最多可调用30次，
     *  这样的话，每秒大概能给600个用户发送消息
     */
    public function sendMessage($from_user = 'admin', $username, $content, $target_type = 'users', Array $ext = [])
    {
        $body['target_type'] = $target_type;
        $body['target'] = (Array)$username;
        $body['msg'] = [
            'type' => 'txt',
            'msg'  => $content
        ];
        $body['from'] = $from_user;
        $body['ext'] = $ext;

        return $this->client->post('messages', [
            'body' => json_encode($body)
        ]);
    }

    /**
     * 添加一个用户到群组
     *
     * @author Xuan
     * @param $group_id
     * @param $user_name
     * @return bool
     */
    public function addMember($group_id, $user_name)
    {
        $url = $this->url . 'chatgroups/' . $group_id . '/users/' . $user_name;

        try{
            $response = $this->client->post($url);
        }catch (RequestException $e){
            $response = $e->getResponse();
        }

        if($response->getStatusCode() != 200){
            return false;
        }

        return true;
    }

    /**
     * 禁用一个用户
     *
     * @author Xuan
     * @param $user_id
     * @return bool
     */
    public function deactivate($user_id)
    {
        $url = $this->url . 'users/' . $user_id . '/deactivate';

        try{
            $response = $this->client->post($url);
        }catch (RequestException $e){
            $response = $e->getResponse();
        }

        if($response->getStatusCode() != 200){
            return false;
        }

        return true;
    }

    /**
     * 解禁一个用户
     *
     * @author Xuan
     * @param $user_id
     * @return bool
     */
    public function activate($user_id)
    {
        $url = $this->url . 'users/' . $user_id . '/activate';

        try{
            $response = $this->client->post($url);
        }catch (RequestException $e){
            $response = $e->getResponse();
        }

        if($response->getStatusCode() != 200){
            return false;
        }

        return true;
    }

    /**
     * 获取 Token
     */
    public function getToken()
    {
        if($this->token) {
            return $this->token;
        }

        $client = new Client([
            'base_url'  =>  $this->url,
        ]);

        $body['grant_type']    = "client_credentials";
        $body['client_id']     = $this->client_id;
        $body['client_secret'] = $this->client_secret;

        $res = $client->post('token', [ 'body' => json_encode($body) ]);

        //TODO: cache the token
        $result = $res->json();

        return $this->token = $result['access_token'];
    }
}
