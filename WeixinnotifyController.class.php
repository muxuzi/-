<?php
namespace Home\Controller;
use Think\Controller;
class WeixinnotifyController extends Controller {
    public function api(){
    	//define your token
    	define("TOKEN", "qsjywdsws");
    	require_once VENDOR_PATH . 'weixinnotify/WechatCallbackapiTest.class.php';
    	$wechatObj = new \WechatCallbackapiTest();
    	// $wechatObj->valid();
    	$wechatObj->responseMsg();

    }
    public function setMenu()
    {
        $str = '{
           "button":[
           {
                "name":"平安活动",
                "type":"view",
                "url":"http://mp.weixin.qq.com/s?__biz=MzU3MzUxNTAxOA==&mid=100000025&idx=1&sn=4d6652462de2cd1f1c01bc3ef4b60001&chksm=7cc1357c4bb6bc6a9173d2daf210afddf571ad1c2029d7bbbd514b500f76158e2a434aa5bac4&scene=18#wechat_redirect"
           },
           {
                "name":"权益交易",
                "type":"view",
                "url":"http://www.wankahui.cn/qsjy/index.php/Home"
           },
           {    
                "name":"帮助中心",
                "sub_button":[
                    {
                      "name":"公司简介",
                      "type":"view",
                      "url":"http://mp.weixin.qq.com/s?__biz=MzU3MzUxNTAxOA==&mid=100000014&idx=1&sn=344911915c450c894f18605ac2f262eb&chksm=7cc1356b4bb6bc7da567b66a98633680893d4812c7cecdba72ac31580bef086dcc55c34ed9c4&scene=18#wechat_redirect"
                    },
                    {
                      "name":"联系客服",
                      "type":"click",
                      "key":"CUSTOM_SERVICE"
                    },
                    {
                      "name":"建议反馈",
                      "type":"click",
                      "key":"FEEDBACK"
                    }]
           }]
         }';
        curl('https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . getToken(), $str);
        echo '设置成功';
    }



}