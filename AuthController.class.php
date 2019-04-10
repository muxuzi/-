<?php
namespace Home\Controller;
use Think\Controller;
class AuthController extends Controller {
    public function index(){
        //授权完跳转的网址
        $path=$_REQUEST['path'];
        // p($path);die;
        $redirect_uri=urlencode("http://".$_SERVER['HTTP_HOST'].__ROOT__."/index.php/Home/Auth/callBack");
        header('Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='
    .C('appid').'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo&state='.$path.
        '#wechat_redirect');
    }
    public function callBack(){
       //获取到的code
        $code = $_REQUEST['code'];
        //授权结束后的回调网址
        $path = $_REQUEST['state'];
        //获取access_token
        $curl = curl_init();

        curl_setopt($curl,CURLOPT_URL,'https://api.weixin.qq.com/sns/oauth2/access_token?appid='
            .C('appid').'&secret='.C('appSecret').'&code='.$code.'&grant_type=authorization_code ');

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

        //获取access_token和openid,转换为数组
        $data = json_decode(curl_exec($curl),true);
        //如果获取成功，根据access_token和openid获取用户的基本信息
        if($data != null && $data['access_token']){

            //获取用户的基本信息，并将用户的唯一标识保存在session中
            curl_setopt($curl,CURLOPT_URL,'https://api.weixin.qq.com/sns/userinfo?access_token='
                .$data['access_token'].'&openid='.$data['openid'].'&lang=zh_CN');

            $user_data = json_decode(curl_exec($curl),true);
            //p($user_data);
            if($user_data != null && $user_data['openid']){

                curl_close($curl);
                //将用户信息存在数据库中,同时将用户在数据库中唯一的标识保存在session中
                $array = [];

                $array['openid'] = $user_data['openid'];
                //$array['name'] = $user_data['nickname'];
                $array['nickname']=base64_encode($user_data['nickname']);
                //过滤微信名中的表情
                // $array['nickname'] = preg_replace_callback('/./u',
                //     function (array $match) {
                //         return strlen($match[0]) >= 4 ? '' : $match[0];
                //     }, $user_data['nickname']);
                //p($array['name']);
                //$array['city']=$user_data['city'];
                $array['picurl'] = $user_data['headimgurl'];
                //$array['subscribe'] = $user_data['subscribe'];
                $array['time']=time();
                $array['username']="w".substr($user_data['openid'],6)."k";
                // $array['username']=createNonceStr(8).substr($user_data['openid'],-8);
				// $array['username']=createNonceStr(8);
                // 我这里只存储了用户的openid,nickname,city,headimgurl
                $model = M('personal');
                //先判断用户数据是不是已经存储了，如果存储了获取用户在数据库中的唯一标识
                 $user_id = $model->where(['openid'=>$array['openid']])->getField('id');
                if($user_id){
                    session('user_id',$user_id);
                    //判断是否关注公众号 发送消息
                    // p($user_id);die;
                }else{
                    //将用户在数据库中的唯一表示保存在session中
                    $user_id = $model->add($array);
                    session('user_id',$user_id);
                    
                }

                //跳转网页
                header('Location:'.$path);
            }else{

                curl_close($curl);

                exit('获取用户信息失败！');

            }
        }else{

            curl_close($curl);

            exit('微信授权失败');
        }
    }
}