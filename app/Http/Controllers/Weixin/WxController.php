<?php

namespace App\Http\Controllers\Weixin;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

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

        //使用 Guzzle
        $client = new Client();

        $xml_str = file_get_contents("php://input");
        $log_str = '>>>>>>>>> '. date("Y-m-d H:i:s") . $xml_str . "\n";
        file_put_contents('logs/wx_event.log',$log_str,FILE_APPEND);        //  日志文件

        $xml_obj = simplexml_load_string($xml_str);

        //处理业务逻辑


        $msg_type = $xml_obj->MsgType;          //消息类型
        $open_id = $xml_obj->FromUserName;      //用户openid
        $app = $xml_obj->ToUserName;            // 公众号ID
        if($msg_type=='image'){                 //处理图片素材
            $media_id = $xml_obj->MediaId;

            // MediaId URL
            $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$media_id;
            $response = $client->get(new Uri($url));

            $headers = $response->getHeaders();     //获取 响应 头信息
            $file_info = $headers['Content-disposition'][0];            //获取文件名

            $file_name =  rtrim(substr($file_info,-20),'"');
            $new_file_name = 'weixin/' .substr(md5(time().mt_rand()),10,8).'_'.$file_name;

            //保存文件
            $rs = Storage::put($new_file_name, $response->getBody());       //保存文件
            if($rs){
                //TODO 保存成功
            }else{
                //TODO 保存失败
            }


            //var_dump($rs);
        }elseif($msg_type=='voice'){            //处理语音素材
            $media_id = $xml_obj->MediaId;
            $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$media_id;
            $amr = file_get_contents($url);
            $file_name = time() . mt_rand(11111,99999) . '.amr';
            $rs = file_put_contents('wx/voice/'.$file_name,$amr);     //保存录音文件
            var_dump($rs);

        }elseif($msg_type=='text'){         //处理文本信息

            //自动回复天气
            if(strpos($xml_obj->Content,'+天气')){
                //echo $xml_obj->Content;echo '</br>';
                //获取城市名
                $city = explode('+',$xml_obj->Content)[0];
                //echo 'City: '.$city;
                $url = 'https://free-api.heweather.net/s6/weather/now?key=HE1904160951011886&location='.$city;
                $arr = json_decode(file_get_contents($url),true);
                //echo '<pre>';print_r($arr);echo '</pre>';

                $fl = $arr['HeWeather6'][0]['now']['tmp'];      //摄氏度
                $wind_dir = $arr['HeWeather6'][0]['now']['wind_dir'];       //风向
                $wind_sc = $arr['HeWeather6'][0]['now']['wind_sc'];       //风力
                $hum = $arr['HeWeather6'][0]['now']['hum'];       //湿度

                $str = "温度: ".$fl."\n" . "风向：". $wind_dir ."\n" . "风力：".$wind_sc . "湿度：".$hum."\n";

                $response_xml = '<xml>
  <ToUserName><![CDATA['.$open_id.']]></ToUserName>
  <FromUserName><![CDATA['.$app.']]></FromUserName>
  <CreateTime>'.time().'</CreateTime>
  <MsgType><![CDATA[text]]></MsgType>
  <Content><![CDATA['.$str.']]></Content>
</xml>';
                echo $response_xml;

            }
        }

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
            $token = $arr['access_token'];
        }

        return $token;

    }
}
