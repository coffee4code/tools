<?php
/**
 * Created by PhpStorm.
 * User: yin
 * Date: 15-1-21
 * Time: 下午2:05
 */

class Wechat {

    private $wechat_ccount = '1062893543@qq.com';   //用户名
    private $wechat_password = 'qwer1234';          //密码
    private $redis_host = '127.0.0.1';              //redis地址
    private $redis_port = '6379';                   //redis端口

    private $wechat_cookie_save_key = 'gis_wechat_cookie';
    private $wechat_token_save_key = 'gis_wechat_token';
    private $wechat_fakeids_save_key = 'gis_wechat_fakeids';
    private $wechat_all_user_info_save_key = 'gis_wechat_all_user_info';
    private $redis_save_expiry = 600;

    private $send_data;                             //提交的数据
    private $getHeader = 0;                         //是否显示Header信息
    private $cookie;
    public $token;                                 //公共帐号TOKEN
    private $host = 'mp.weixin.qq.com';             //主机
    private $origin = 'https://mp.weixin.qq.com';
    private $referer;                               //引用地址
    private $pageSize = 100000;                     //每页用户数（用于读取所有用户）
    private $userAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20100101 Firefox/23.0";

    public $fakeids;                             //所有粉丝的fakeid

    /**
     * 构造
     * @param array $options
     */
    public function __construct($options=array()){

        if(isset($options['account'])){
            $this->wechat_ccount = $options['account'];
        }
        if(isset($options['password'])){
            $this->wechat_password = $options['password'];
        }

        $this->weChat();

    }

    /**
     * 初始化
     */
    private function weChat(){

        $save_session = $this->getSession();

        $this->cookie = $save_session['cookie'];
        $this->token = $save_session['token'];
        $this->fakeids = $save_session['fakeids'];

    }

    /**
     * 模拟获取session
     * @return array
     */
    private function getSession(){

        $session = array();
        if(class_exists('Redis')){
            $redis = new Redis();
            $redis->pconnect($this->redis_host, $this->redis_port);
            if ($redis and $redis->exists($this->wechat_cookie_save_key)) {
                $cookie = $redis->get($this->wechat_cookie_save_key);
                $token = $redis->get($this->wechat_token_save_key);
                $fakeids = $redis->get($this->wechat_fakeids_save_key);
                if(!empty($cookie) and !empty($token)){
                    $session['cookie'] = $cookie;
                    $session['token'] = $token;
                    $session['fakeids'] = json_decode($fakeids);

                    $check = $this->check($session);
                    if(!$check){
                        $session = $this->login();
                    }
                }else{
                    $session = $this->login();
                }

            }else{
                $session = $this->login();
            }
        }
        return $session;

    }

    /**
     * 模拟保存session
     * @param $session
     */
    private function setSession($session){

        if(class_exists('Redis')){
            $redis = new Redis();
            $redis->pconnect($this->redis_host, $this->redis_port);

            $redis->setex($this->wechat_cookie_save_key,$this->redis_save_expiry,$session['cookie']);
            $redis->setex($this->wechat_token_save_key,$this->redis_save_expiry,$session['token']);
            $redis->setex($this->wechat_fakeids_save_key,$this->redis_save_expiry,json_encode($session['fakeids']));
        }

    }

    /**
     * 检测cookie的有效性(调用获取位置信息的方法)
     * 响应示例：'{"base_resp":{"ret":0,"err_msg":"ok"},"num":34,"data":[{"id":10185,"name":"上海"},{"id":10192,"name":"云南"},{"id":10175,"name":"内蒙古"},{"id":10161,"name":"北京"},{"id":10188,"name":"台湾"},{"id":10178,"name":"吉林"},{"id":10187,"name":"四川"},{"id":10189,"name":"天津"},{"id":10181,"name":"宁夏"},{"id":10160,"name":"安徽"},{"id":10184,"name":"山东"},{"id":10186,"name":"山西"},{"id":10165,"name":"广东"},{"id":10166,"name":"广西"},{"id":10191,"name":"新疆"},{"id":10176,"name":"江苏"},{"id":10177,"name":"江西"},{"id":10169,"name":"河北"},{"id":10171,"name":"河南"},{"id":10193,"name":"浙江"},{"id":10168,"name":"海南"},{"id":10173,"name":"湖北"},{"id":10174,"name":"湖南"},{"id":10180,"name":"澳门"},{"id":10164,"name":"甘肃"},{"id":10163,"name":"福建"},{"id":10190,"name":"西藏"},{"id":10167,"name":"贵州"},{"id":10179,"name":"辽宁"},{"id":10162,"name":"重庆"},{"id":10183,"name":"陕西"},{"id":10182,"name":"青海"},{"id":10172,"name":"香港"},{"id":10170,"name":"黑龙江"}]}';
     */
    private function check($session){

        $this->cookie = $session['cookie'];
        $this->token = $session['token'];
        $this->fakeids = $session['fakeids'];

        $url = "https://mp.weixin.qq.com/cgi-bin/getregions?id=1017&t=ajax-getregions&lang=zh_CN";
        $this->referer = "https://mp.weixin.qq.com/cgi-bin/home?t=home/index&lang=zh_CN&token={$this->token}";

        $response = $this->curlPost($url);

        $res = json_decode($response,true);

        return isset($res['num'])?true:false;

    }

    /**
     * 模拟登陆
     * @return array
     */
    private function login(){

        $url = 'https://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN';
        $this->send_data = array(
            'username' => $this->wechat_ccount,
            'pwd' => md5($this->wechat_password),
            'f' => 'json'
        );
        $this->referer = "https://mp.weixin.qq.com/";
        $this->getHeader = 1;
        $result = explode("\n",$this->curlPost($url));

        $cookie = '';
        $token = '';
        $fakeids = array();
        $session = array();
        $login_success = false;
        foreach ($result as $value) {
            $value = trim($value);
            if(preg_match('/ret":([-\d]+),/i', $value,$match)){                     //获取状态码
                switch ($match[1]) {
                    case 0:
                        $login_success = true;
                        break;
                    default:
                        die(json_encode(array('errCode'=>$match[1])));
                }
            }
            if(preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $value,$match)){   //获取cookie
                $cookie .=$match[1].'='.$match[2].'; ';
            }
            if(preg_match('/token=(.+)"}$/i', $value,$match)){                      //获取token
                $token = $match[1];
            }
        }

        $session['token'] = $token;
        $session['cookie'] = $cookie;
        $session['fakeids'] = $fakeids;
        $this->setSession($session);

        if(!empty($cookie) and $login_success){
            $fakeids = $this->getFakeIds($token);
        }


        return $session;

    }

    /**
     * 获取所有用户的fakeid
     * @param $token
     * @return array
     */
    private function getFakeIds($token){
        ini_set('max_execution_time',600);
        $pageSize = 1000000;
        $this->referer = "https://mp.weixin.qq.com/cgi-bin/home?t=home/index&lang=zh_CN&token={$token}";
        $url = "https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize={$pageSize}&pageidx=0&type=0&groupid=0&token={$token}&lang=zh_CN";
        $user = $this->curlGet($url);
        $preg = "/\"id\":(\d+),\"nick_name\"/";
        preg_match_all($preg,$user,$b);
        $arr = array();
        $i = 0;
        foreach($b[1] as $v){
            $url = 'https://mp.weixin.qq.com/cgi-bin/contactmanage?t=user/index&pagesize='.$pageSize.'&pageidx=0&type=0&groupid='.$v.'&token='.$token.'&lang=zh_CN';
            $user = $this->curlGet($url);
            $preg = "/\"id\":(\d+),\"nick_name\"/";
            preg_match_all($preg,$user,$a);
            foreach($a[1] as $vv){
                $arr[$i]['fakeid'] = $vv;
                $arr[$i]['groupid'] = $v;
                $i++;
            }
        }
        return $arr;
    }

    /**
     * curl模拟post方法
     * @param $url
     * @param $language
     * @return mixed
     */
    private function curlPost($url) {
        $header = array(
            'Accept:*/*',
            'Accept-Charset:GBK,utf-8;q=0.7,*;q=0.3',
            'Accept-Encoding:gzip,deflate,sdch',
            'Accept-Language:zh-CN,zh;q=0.8',
            'Connection:keep-alive',
            'Host:'.$this->host,
            'Origin:'.$this->origin,
            'Referer:'.$this->referer,
            'X-Requested-With:XMLHttpRequest'
        );
        $curl = curl_init(); //启动一个curl会话
        curl_setopt($curl, CURLOPT_URL, $url); //要访问的地址
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //设置HTTP头字段的数组
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent); //模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); //使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); //自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); //发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->send_data); //Post提交的数据包
        curl_setopt($curl, CURLOPT_COOKIE, $this->cookie); //读取储存的Cookie信息
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); //设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, $this->getHeader); //显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //获取的信息以文件流的形式返回
        $result = curl_exec($curl); //执行一个curl会话
        curl_close($curl); //关闭curl
        return $result;
    }

    /**
     * 模拟get方法
     * @param $url
     * @param $language
     * @return mixed
     */
    private function curlGet($url){
        $header = array(
            'Accept:*/*',
            'Accept-Encoding:gzip,deflate,sdch',
            'Accept-Language:zh-CN,zh;q=0.8',
            'Connection:keep-alive',
            'Host:mp.weixin.qq.com',
            'Referer:'.$this->referer,
            'charset=UTF-8',
            'X-Requested-With:XMLHttpRequest'
        );

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //设置HTTP头字段的数组
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_HTTPGET, 1); // 发送一个常规的GET请求
        curl_setopt($curl, CURLOPT_COOKIE, $this->cookie); // 读取上面所储存的Cookie信息
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, $this->getHeader); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    /**
     * 获取所有用户的信息
     * @return array
     */
    public function getAllUserInfo(){

        $info = array();
        if(class_exists('Redis')){
            $redis = new Redis();
            $redis->pconnect($this->redis_host, $this->redis_port);
            if ($redis->exists($this->wechat_all_user_info_save_key)) {
                $info = json_decode($redis->get($this->wechat_all_user_info_save_key));
            }else{
                if($this->fakeids){
                    foreach($this->fakeids as $v){
                        $info[] = $this->getUserInfo($v['groupid'],$v['fakeid']);
                    }
                }
                $redis->setex($this->wechat_all_user_info_save_key,$this->redis_save_expiry,json_encode($info));
            }
        }



        return $info;
    }

    /**
     * 获取单个用户的信息
     * @param $groupId
     * @param $fakeId
     * @return mixed
     */
    public function getUserInfo($groupId,$fakeId){
        $url = "https://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&fakeid={$fakeId}";
        $this->getHeader = 0;
        $this->referer = 'https://mp.weixin.qq.com/cgi-bin/contactmanagepage?token='.$this->token.'&t=wxm-friend&lang=zh_CN&pagesize='.$this->pageSize.'&pageidx=0&type=0&groupid='.$groupId;
        $this->send_data = array(
            'token'=>$this->token,
            'ajax'=>1
        );
        $message_opt = $this->curlPost($url);
        return $message_opt;
    }

    /**
     * @param $fakeid
     * @param $content
     * @return mixed
     */
    private function sendSingle($fakeid,$content){
        $url = 'https://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&lang=zh_CN';
        $this->send_data = array(
            'type' => 1,
            'content' => $content,
            'error' => 'false',
            'tofakeid' => $fakeid,
            'token' => $this->token,
            'ajax' => 1,
        );
        $this->referer = 'https://mp.weixin.qq.com/cgi-bin/singlemsgpage?token='.$this->token.'&fromfakeid='.$fakeid.'&msgid=&source=&count=20&t=wxm-singlechat&lang=zh_CN';
        return $this->curlPost($url);
    }

    /**
     * @param string $content
     * @param string $userId
     * @return string
     */
    public function sendBatch($userId='',$content='') {
        $errUser = array();
        if(!is_array($userId) || empty($userId)){
            $userId = $this->fakeids;
        }
        if(is_array($userId) && !empty($userId)){
            foreach($userId as $v){
                @$json = json_decode($this->sendSingle($v,$content));
                if(isset($json->base_resp)){
                    $base_resp = $json->base_resp;
                    if(isset($base_resp->ret) and $base_resp->ret!=0){
                        $errUser[] = $v;
                    }
                }
            }
        }

        //共发送用户数
        $count = count($userId);
        //发送失败用户数
        $errCount = count($errUser);
        //发送成功用户数
        $succeCount = $count-$errCount;

        $data = array(
            'status'=>0,
            'count'=>$count,
            'succeCount'=>$succeCount,
            'errCount'=>$errCount,
            'errUser'=>$errUser
        );

        return json_encode($data);
    }
}