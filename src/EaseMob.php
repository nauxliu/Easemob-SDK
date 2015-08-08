<?php namespace Naux\EaseMob;

use BadFunctionCallException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;

class EaseMob
{
    /**
     * @var \GuzzleHttp\Client;
     */
    private $client;

    private $client_id;

    private $client_secret;

    private $org_name;

    private $app_name;

    private $url;

    private $token;

    private $last_response;


    public function __construct($client_id, $client_secret, $org_name, $app_name, $server_url, $token = null)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->org_name = $org_name;
        $this->app_name = $app_name;
        $this->url = $server_url . '/' . $org_name . '/' . $app_name . '/';
        $token = $token ?: $this->getToken();

        $this->client = new Client([
            'base_url' => $this->url,
            'defaults' => [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ],
        ]);
    }

    /**
     * 获取单个群组信息
     *
     * @param $group_id
     */
    public function groupDetails($group_id)
    {
        return $this->groupsDetails((array)$group_id);
    }

    /**
     * 获取多个聊天室信息
     *
     * @param array $group_ids
     */
    public function groupsDetails(Array $group_ids)
    {
        $groups_str = implode(',', $group_ids);
        $url = $this->url . 'chatgroups/' . $groups_str;

        return $this->result($this->get($url));
    }

    /**
     * 更新群组信息
     *
     * @param $group_id
     * @param $groupname
     * @param $description
     * @param $maxusers
     * @return bool
     */
    public function updateGroup($group_id, $groupname = null, $description = null, $maxusers = null)
    {
        $url = $this->url . 'chatgroups/' . $group_id;

        $response = $this->put($url, [
            'body' => array_filter(compact('groupname', 'description', 'maxusers'))
        ]);

        return $response->getStatusCode() == 200;
    }

    /**
     * 获取用户详细个人资料
     *
     * @param $username
     * @return Array
     */
    public function userDetails($username)
    {
        $response = $this->get('users/' . $username);
        return $this->result($response);
    }

    /**
     * 获取聊天记录
     *
     * @param string $ql
     * @param string $cursor
     * @param int $limit
     * @return Array
     */
    public function chatRecord($ql = '', $cursor = '', $limit = 20)
    {
        $query = [];

        $query['ql'] = !empty($ql) ? $ql : "order+by+timestamp+desc";
        $query['cursor'] = $cursor;
        $query['limit'] = $limit;

        //delete empty query
        foreach ($query as $key => $value) {
            if (empty($value)) {
                unset($query[$key]);
            }
        }

        return $this->get('chatmessages?' . http_build_query($query))->json();
    }

    /**
     * 向用户发送消息
     *
     * @param string $from_user 发送者用户名
     * @param array $username array('1','2') 接收者
     * @param string $target_type 默认为：users。向用户发消息: users, 向群组发送消息: chatgroups
     * @param string $content 消息内容
     * @param array $ext 自定义扩展字段
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    public function sendMessage($from_user = 'admin', $username, $content, $target_type = 'users', Array $ext = [])
    {
        $body['target_type'] = $target_type;
        $body['target'] = (Array)$username;
        $body['msg'] = [
            'type' => 'txt',
            'msg' => $content
        ];
        $body['from'] = $from_user;
        $body['ext'] = $ext;

        return $this->post('messages', [
            'body' => $body
        ]);
    }

    /**
     * 添加一个用户到群组
     *
     * @param $group_id
     * @param $user_name
     * @return bool
     */
    public function addMember($group_id, $user_name)
    {
        $url = $this->url . 'chatgroups/' . $group_id . '/users/' . $user_name;

        $response = $this->post($url);

        return $response->getStatusCode() == 200;
    }

    /**
     * 添加一组用户到群组
     *
     * @param $group_id
     * @param $user_names
     * @return bool
     */
    public function addMembers($group_id, $user_names)
    {
        $url = $this->url . 'chatgroups/' . $group_id . '/users';

        $response = $this->post($url, [
            'body' => $user_names,
        ]);

        return $response->getStatusCode() == 200;
    }

    /**
     * 禁用一个用户
     *
     * @param $user_id
     * @return bool
     */
    public function deactivate($user_id)
    {
        $url = $this->url . 'users/' . $user_id . '/deactivate';

        $response = $this->post($url);

        return $response->getStatusCode() == 200;
    }

    /**
     * 解禁一个用户
     *
     * @param $user_id
     * @return bool
     */
    public function activate($user_id)
    {
        $url = $this->url . 'users/' . $user_id . '/activate';

        $response = $this->post($url);

        return $response->getStatusCode() == 200;
    }


    /**
     * 往黑名单中添加用户
     *
     * @param $owner_user_name 黑名单拥有者
     * @param $user_name 可传字符串或数组，数组为多个用户
     * @return bool
     */
    public function addToBlocks($owner_user_name, $user_name)
    {
        $url = $this->url . 'users/' . $owner_user_name . '/blocks/users';
        $response = $this->post($url, [
            'body' => ['usernames' => (array)$user_name]
        ]);

        return $response->getStatusCode() == 200;
    }

    /**
     * 把用户从黑名单移除
     *
     * @param $owner_user_name 黑名单拥有者
     * @param $user_name 要移出黑名单的用户
     * @return bool
     */
    public function removeFromBlocks($owner_user_name, $user_name)
    {
        $url = $this->url . 'users/' . $owner_user_name . '/blocks/users/' . $user_name;
        $response = $this->delete($url);

        return $response->getStatusCode() == 200;
    }

    /**
     * 获取 Token
     *
     * @return String
     */
    public function getToken()
    {
        if ($this->token) {
            return $this->token;
        }

        $client = new Client([
            'base_url' => $this->url,
        ]);

        $body['grant_type'] = 'client_credentials';
        $body['client_id'] = $this->client_id;
        $body['client_secret'] = $this->client_secret;

        $response = $client->post('token', ['body' => $this->encode($body)]);

        $this->last_response = $response;
        $result = $response->json();

        return $this->token = $result['access_token'];
    }

    /**
     * 获取最后的一个响应对象
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }

    /**
     * 获取最后的一个响应内容
     */
    public function getLastResponseContent()
    {
        return $this->last_response->json();
    }

    /**
     * 环信要求请求数据用 json 格式
     *
     * @param $value
     * @return ResponseInterface
     */
    private function encode($value)
    {
        return json_encode($value);
    }

    public function __call($func, $args)
    {
        if (!in_array($func, ['get', 'post', 'head', 'delete', 'put', 'patch', 'options']) && count($args) < 1) {
            throw new BadFunctionCallException();
        }

        if (count($args) == 2) {
            list($url, $options) = $args;

            if (isset($options['body'])) {
                $options['body'] = $this->encode($options['body']);
            }

            $args = [$url, $options];
        }

        try {
            $response = call_user_func_array([$this->client, $func], $args);
        } catch (RequestException $e) {
            $response = $e->getResponse();
        }

        $this->last_response = $response;

        return $response;
    }

    /**
     * @param $response
     * @return bool
     */
    public function result($response)
    {
        if ($response->getStatusCode() != 200) {
            return false;
        }

        return $response->json();
    }
}
