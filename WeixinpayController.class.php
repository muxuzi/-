<?php

namespace Home\Controller;

use Think\Controller;

class WeixinpayController extends  Controller {
    //支付宝支付
    public function alipay(){
        if(I('o_number')){
            vendor('Alipay.wappay.buildermodel.AlipayTradeWapPayContentBuilder');
            vendor('Alipay.AopSdk');
            vendor('Alipay.wappay.service.AlipayTradeService');
            $config = C('alipayconfig');
            //商户订单号，商户网站订单系统中唯一订单号，必填
            $out_trade_no = I('o_number');
            //订单名称，必填
            $subject = '商品购买';
            //付款金额，必填
            //$total_amount = $_POST['sumPrice'];
            $total_amount = M('order')->where(array('o_number'=>$out_trade_no))->getField('sum');
            $total_amount = 0.01;
            //商品描述，可空
            $body = '';
            //超时时间
            $timeout_express="1m";
            $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
            $payRequestBuilder->setBody($body);
            $payRequestBuilder->setSubject($subject);
            $payRequestBuilder->setOutTradeNo($out_trade_no);
            $payRequestBuilder->setTotalAmount($total_amount);
            $payRequestBuilder->setTimeExpress($timeout_express);
            $payResponse = new \AlipayTradeService($config);
            $result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
            file_put_contents('alipay.txt',$result);
            return $result;
        }
    }
    //支付宝回调函数
    public function notifyurl(){
        $config = C('alipayconfig');
        require_once(VENDOR_PATH . 'Alipay/AopSdk.php');
        vendor('Alipay.wappay.service.AlipayTradeService');
        $arr=$_POST;
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        //file_put_contents('alinotify1.txt',$arr,FILE_APPEND);
        if($result){
            //请在这里加上商户的业务逻辑程序代
            $attach = $_POST['out_trade_no'];//商户订单号
            $trade_no = $_POST['trade_no'];//支付宝交易号
            $trade_status = $_POST['trade_status'];//交易状态
            if($trade_status == 'TRADE_SUCCESS'){
                $data2['status'] = 1;
                $data2['payment'] = 1;
                $data2['paytime'] = time();
                $pay=M('order')->where(array('o_number'=>$attach))->save($data2);
                if($pay){
                    // 订单产品待使用
                    $data3['status']=1;
                    M('order_sell')->where(array('oid'=>$attach))->save($data3);
                    // 商家id
                    $order=M('order')->where(array('o_number'=>$attach))->find();
                    M("personal")->where("id=".$order["uid"])->setInc("score",floor(15+$order["sum"]/50));
                    M("product")->where("id=".$order["pid"])->setInc("t_deal_num");
                    $cid=M("product")->where("id=".$order["pid"])->getField("cid");
                    M("classify")->where("id=".$cid)->setInc("c_deal_num");
                    $sell_list=M("order_sell")->where("oid=".$order["o_number"])->select();
                    foreach($sell_list as $sk=>$sv){
                        $sell=M("sell")->find($sv["sid"]);
                        $sell_list[$sk]["uid"]=$sell["uid"];
                        $sell_list[$sk]["price"]=$sell["price"];
                    }
                    //file_put_contents("9.txt",M('sell')->getLastSql());
                    // 找到订单对应的商品改为已售
                    $sellwh['sell_status']=2;
                    $sellwh['sell_soldtime']=time();
                    $sellwh['freeze']=1;
                    $sell_id=M('order_sell')->where(array('oid'=>$attach))->select();
                    
                    foreach ($sell_id as $key => $value) {
                        M('sell')->where(array('id'=>$value['sid']))->save($sellwh);
                        
                    }
                    // 价格存放在冻结里
                    foreach($sell_list As $k=>$v){
                        M('personal')->where(array('id'=>$v['uid']))->setInc('ncash',$v['price']);
                        //file_put_contents("10.txt",M('personal')->getLastSql());
                        M("personal")->where("id=".$v['uid'])->setInc("score",floor(15+$v["price"]/50));
                        
                    }
                    //file_put_contents("8.txt",M('personal')->getLastSql());
                    // 模板推送
                    $temp=M("order_sell")->where("oid=".$order["o_number"])->select();
                    $template_id =C('template');
                    $url="";
                    foreach ($temp as $key => $value) {
                         $template_sell=M("sell")->where(array('id'=>$value['sid']))->find();
                         // 商品
                         $products=M('product')->where(array('id'=>$template_sell['pid']))->find();
                         // 类型标题
                         $class_name=M('classify')->where(array('id'=>$products['cid']))->find();
                         // 来源
                         $source=M('source')->where(array('id'=>$products['sid']))->find();
                        
                         // 商家
                         $openid=M('personal')->where(array('id'=>$template_sell['uid']))->getfield('openid');
                         $data=array(
                            'first'=>array('value'=>urlencode('您好，您寄卖的一件商品已成功售出！'),'color'=>"#FFA500"),
                            'keyword1'=>array('value'=>urlencode($class_name['c_title'].'-'.$source['s_title']),'color'=>'#00008B'),
                            'keyword2'=>array('value'=>urlencode(date('Y-m-d H:i:s',$order['paytime'])),'color'=>'#00008B'),
                            'keyword3'=>array('value'=>urlencode($template_sell['price']),'color'=>'#00008B'),
                            'remark'=>array('value'=>urlencode('感谢您对玩卡惠权益的支持！'),'color'=>'#00008B'),
                        );
                        sendTemplate($openid,$template_id,$url,$data);
                    }
                    
                    $result = true;
                }else{
                    $result = false;
                }
            }
            echo "success";
        }else{
            //验证失败
            echo "fail";
        }
    }
    //支付成功跳转页面
    public function returnurl(){
        $config = C('alipayconfig');
        require_once(VENDOR_PATH . 'Alipay/AopSdk.php');
        require_once(VENDOR_PATH . 'Alipay/wappay/service/AlipayTradeService.php');
        $arr=$_GET;
        //p($arr);
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($arr);
        //file_put_contents('log.txt',$result,FILE_APPEND);
        //验证成功
       if($result){
            //商户订单号
            //$out_trade_no = htmlspecialchars($_GET['out_trade_no']);
            //支付宝交易号
            //$trade_no = htmlspecialchars($_GET['trade_no']);
            //echo "验证成功<br />外部订单号：".$out_trade_no;
            //tips('支付成功',U('Personal/index'));
           $this->redirect('Personal/index');
        }else{
            //验证失败

           echo "验证失败";

       }

    }
    //微信支付打印订单
    public function payorder(){
        $o_number=I('o_number');
        $money=M('order')->where(array('o_number'=>$o_number))->getField('sum');
        $money=0.01;
        //引入微信支付相关类
        ini_set('date.timezone','Asia/Shanghai');
        Vendor('wxpay.lib.WxPayApi');
        Vendor('wxpay.example.JsApiPay');
        Vendor('wxpay.example.log');
        //初始化日志
        $logHandler= new \CLogFileHandler("./logs/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
        //打印输出数组信息
        function printf_info($data)
        {
            foreach($data as $key=>$value){

                echo "<font color='#00ff55;'>$key</font> : $value <br/>";
            }

        }

        // //①、获取用户openid
        $tools = new \JsApiPay();
        $openid=M('personal')->where(array('id'=>session('user_id')))->getField('openid');

        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("支付");
        $input->SetAttach($o_number);
        $input->SetOut_trade_no(\WxPayConfig::MCHID.date("YmdHis"));
        // $input->SetOut_trade_no($sm);//商户单号
        //file_put_contents('order.txt',$sm,FILE_APPEND);
        $input->SetTotal_fee($money*100);
        $input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");//订单优惠标记
        $input->SetNotify_url("http://".$_SERVER['HTTP_HOST'].__ROOT__."/index.php/Home/Weixinpay/ordernotify");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $order = \WxPayApi::unifiedOrder($input);
        // file_put_contents('log12.txt',$order,FILE_APPEND);
        // echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        //printf_info($order);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        //获取共享收货地址js函数参数
        // $editAddress = $tools->GetEditAddressParameters();
        //③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
        /**
         * 注意：
         * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
         * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
         * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
         */
        echo $jsApiParameters;exit;
    }
    //微信支付订单回调
    public function ordernotify(){
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        file_put_contents('log.txt',$xml,FILE_APPEND);
        //将服务器返回的XML数据转化为数组
        $data = json_decode(json_encode(simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA)),true);
        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        $sign = $this->makeSign($data);
        // file_put_contents('log1.txt',$data['out_trade_no'],FILE_APPEND);
        // 判断签名是否正确  判断支付状态
        // ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS')
        if (($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
            //获取服务器返回的数据
            // $out_trade_no = $data['out_trade_no'];  //商户单号
            $attach = $data['attach'];        //附加参数,选择传递订单ID
            //$str= $data['time_end'];          //付款时时间20170302122345
            //$total_fee = $data['total_fee'];    //付款金额
            //$transaction_id=$data['transaction_id'];//交易单号
            // $openid=$data['openid'];
            //$total_fee = $total_fee/100;
            //写入数据库
            //$st=explode(',',$attach);
            //$data2['o_number'] = $st[0];
            $data2['status'] = 1;
            $data2['payment'] = 1;
            $data2['paytime'] = time();
            $pay=M('order')->where(array('o_number'=>$attach))->save($data2);
			
            if($pay){
                // 订单产品
                $data3['status']=1;
                M('order_sell')->where(array('oid'=>$attach))->save($data3);
               // 商家id
                $order=M('order')->where(array('o_number'=>$attach))->find();
				M("personal")->where("id=".$order["uid"])->setInc("score",floor(15+$order["sum"]/50));
				M("product")->where("id=".$order["pid"])->setInc("t_deal_num");
				$cid=M("product")->where("id=".$order["pid"])->getField("cid");
				M("classify")->where("id=".$cid)->setInc("c_deal_num");
				$sell_list=M("order_sell")->where("oid=".$order["o_number"])->select();
				foreach($sell_list as $sk=>$sv){
					$sell=M("sell")->find($sv["sid"]);
					$sell_list[$sk]["uid"]=$sell["uid"];
					$sell_list[$sk]["price"]=$sell["price"];
				}
				//file_put_contents("9.txt",M('sell')->getLastSql());
                // 找到订单对应的商品改为已售
                $sellwh['sell_status']=2;
				$sellwh['sell_soldtime']=time();
				$sellwh['freeze']=1;
                $sell_id=M('order_sell')->where(array('oid'=>$attach))->select();
				
                foreach ($sell_id as $key => $value) {
                    M('sell')->where(array('id'=>$value['sid']))->save($sellwh);
                }
                // 价格存放在冻结里
				foreach($sell_list As $k=>$v){
					M('personal')->where(array('id'=>$v['uid']))->setInc('ncash',$v['price']);
					//file_put_contents("10.txt",M('personal')->getLastSql());
					M("personal")->where("id=".$v['uid'])->setInc("score",floor(15+$v["price"]/50));
					
				}
				//file_put_contents("8.txt",M('personal')->getLastSql());
                // 模板推送
                $temp=M("order_sell")->where("oid=".$order["o_number"])->select();
                $template_id =C('template');
                $url="";
                foreach ($temp as $key => $value) {
                     $template_sell=M("sell")->where(array('id'=>$value['sid']))->find();
                     // 商品
                     $products=M('product')->where(array('id'=>$template_sell['pid']))->find();

                     // 类型标题
                     $class_name=M('classify')->where(array('id'=>$products['cid']))->find();
                     // 来源
                     $source=M('source')->where(array('id'=>$products['sid']))->find();
                      //标题
                    // $title=M("product")->where(array('sid'=>$template_sell['sid']))->find();

                     // 商家
                     $openid=M('personal')->where(array('id'=>$template_sell['uid']))->getfield('openid');
                     $data=array(
                        'first'=>array('value'=>urlencode('您好，您寄卖的一件商品已成功售出！'),'color'=>"#FFA500"),
                        'keyword1'=>array('value'=>urlencode($source['s_title'].'-'.$products['t_title']),'color'=>'#00008B'),
                        'keyword2'=>array('value'=>urlencode(date('Y-m-d H:i:s',$order['paytime'])),'color'=>'#00008B'),
                        'keyword3'=>array('value'=>urlencode($template_sell['price']),'color'=>'#00008B'),
                        'remark'=>array('value'=>urlencode('感谢您对玩卡惠权益的支持！'),'color'=>'#00008B'),
                    );
                    sendTemplate($openid,$template_id,$url,$data);
                }
                // $template_id ="Zlyn81dQN_hyHoEqDAnbrnpobSuLgJLE5OfcemfCCFE";
                // $url="";
                // foreach ($temp as $key => $value) {
                //      $template_sell=M("sell")->where(array('id'=>$value['sid']))->find();
                //      $openid=M('personal')->where(array('id'=>$template_sell['uid']))->getField('openid');
                //      $data=array(
                //         'first'=>array('value'=>urlencode('出售提醒'),'color'=>"#FFA500"),
                //         'keyword1'=>array('value'=>urlencode($attach),'color'=>'#00008B'),
                //         'keyword2'=>array('value'=>urlencode(date('Y-m-d H:i:s',$order['paytime'])),'color'=>'#00008B'),
                //         'keyword3'=>array('value'=>urlencode($template_sell['price']),'color'=>'#00008B'),
                //         'keyword4'=>array('value'=>urlencode('订单信息'),'color'=>'#00008B'),
                //         'remark'=>array('value'=>urlencode('您的商品已售出！！！'),'color'=>'#00008B'),
                //     );
                //     sendTemplate($openid,$template_id,$url,$data);
                // }
                
                $result = true;
            }else{
                $result = false;
            }
        }else{
            $result = false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
        return $result;
    }

    /**
    * 生成签名 
    * @return 签名，本函数不覆盖sign成员变量 

    */  
    protected function makeSign($data){  
        //获取微信支付秘钥  
        // require_once APP_ROOT."/Api/wxpay/lib/WxPay.Api.php";  
        Vendor('wxpay.lib.WxPayApi');
        $key = \WxPayConfig::KEY;  
        // 去空  
        $data=array_filter($data);  
        //签名步骤一：按字典序排序参数  
        ksort($data);  
        $string_a=http_build_query($data);  
        $string_a=urldecode($string_a);  
        //签名步骤二：在string后加入KEY  
        //$config=$this->config;  
        $string_sign_temp=$string_a."&key=".$key;  
        //签名步骤三：MD5加密  
        $sign = md5($string_sign_temp);  
        // 签名步骤四：所有字符转为大写  
        $result=strtoupper($sign);  
        return $result;  
    }

    //生成签名的随机串
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

}