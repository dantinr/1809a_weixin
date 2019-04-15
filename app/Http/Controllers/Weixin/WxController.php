<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Redis;

class WxController extends Controller
{
    //


    //首次接入
    public function valid()
    {
        echo $_GET['echostr'];
    }

    /**
     * 接收微信推送事件
     */
    public function wxEvent()
    {
        $xml_str = file_get_contents("php://input");
        $log_str = '>>>>>>>>> '. date("Y-m-d H:i:s") . $xml_str . "\n";
        file_put_contents('logs/wx_event.log',$log_str,FILE_APPEND);        //  日志文件

    }

    public function getAccessToken()
    {

        //先获取缓存，如果不存在则请求接口
        $redis_key = 'wx_access_token';
        $token = Redis::get($redis_key);
        if($token){
            echo 'Cache: ';echo '</br>';
        }else{
            echo 'No Cache: ';echo '</br>';
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env("WX_APPID").'&secret='.env("WX_APP_SECRET");
            //echo $url;die;
            $json_str = file_get_contents($url);
            $arr = json_decode($json_str,true);
            echo '<pre>';print_r($arr);echo '</pre>';

            Redis::set($redis_key,$arr['access_token']);
            Redis::expire($redis_key,3600);         //设置过期时间
        }

        return $token;

    }
}
