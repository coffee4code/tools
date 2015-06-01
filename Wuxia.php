<?php
/**
 * Created by PhpStorm.
 * User: yin
 * Date: 15-1-21
 * Time: 下午2:05
 */

class Wuxia {

    private $getHeader = 0;
    private $cookie = 'o_cookie=1062893543; pgv_pvi=4042385408; pgv_si=s7394071552; RK=11dnMJCcRE; 1732733721_logtime=1432778451; pt_clientip=f147790f0959c409; pt_serverip=fd0d0a8702dc4e96; ptui_loginuin=1732733721; ptisp=ctc; ptcz=d6bc1317738b8271b051b183bdc2d26e7712525da3326c977084daccb7ebfa80; pt2gguin=o1732733721; p_uin=o1732733721; p_skey=0r12AeyUI4eGLxpQ*vILVMr7s*P2iU-SGPG-H4FdTLs_; pt4_token=3xb3mLkytJDzxgGBnnHxfg__; ied_rf=wb.qq.com/activity/a20150513tdqh/index.html; pgv_pvid=2816319138; pgv_info=pgvReferrer=&ssid=s9443140172; verifysession=h0179156b29a78a991a63418cf3ccd6e688c4866202b3adb7ded27c5e5b6f52c69b735af30604c809c2; 1732733721_centergid=2';


    private $host = 'igame.qq.com';
    private $url = 'http://igame.qq.com/interface/fn/index.php?logicname=c_sendgift_getActGift&actname=a20150513tdqh&giftname=1&g_tk=890335917&json=1';
    private $referer = 'http://igame.qq.com/acts/a20150513tdqh/index.php';


    private $userAgent = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36";

    /**
     * 构造
     * @param array $options
     */
    public function __construct(){

    }

    public function keygen(){

        return $this->curlGet($this->url);

    }

    /**
     * 模拟get方法
     * @param $url
     * @return mixed
     */
    private function curlGet($url){
        $header = array(
            'Accept:application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding:text',
            'Accept-Language:zh-TW,zh;q=0.8,en-US;q=0.6,en;q=0.4,zh-CN;q=0.2',
            'Connection:keep-alive',
            'Cache-Control:no-cache',
            'DNT:1',
            'Pragma:no-cache',
            'RA-Sid:3DB781BB-20141218-071857-5b124b-9e83e3',
            'RA-Ver:2.10.4',
            'Host:'.$this->host,
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

        return json_decode($tmpInfo);
    }

}