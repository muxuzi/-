<?php

namespace Home\Controller;

use Think\Controller;

class OrderController extends Controller { //支付宝控制器AlipayController

    //支付宝支付
    public function alipay(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false) {
            // 非微信浏览器禁止浏览
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
                echo $result;
                // // return $result;
            }
        }else {
            // 微信浏览器，允许访问
            
            $this->assign('type',1);
            $this->display();
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
            // $attach = $_POST['out_trade_no'];//商户订单号
            // $trade_no = $_POST['trade_no'];//支付宝交易号
            // $trade_status = $_POST['trade_status'];//交易状态
            // if($trade_status == 'TRADE_SUCCESS'){
            //         $result = true;
            //     }else{
            //         $result = false;
            //     }
            // }
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
            $attach=htmlspecialchars($_GET['out_trade_no']);
            $data2['status'] = 1;
            $data2['payment'] = 0;
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
                //file_put_contents("8.txt",M('personal')->getLastSql());
                // // 模板推送
                // $temp=M("order_sell")->where("oid=".$order["o_number"])->select();
                // $template_id ="Zlyn81dQN_hyHoEqDAnbrnpobSuLgJLE5OfcemfCCFE";
                // $url="";
                // foreach ($temp as $key => $value) {
                //      $template_sell=M("sell")->where(array('id'=>$value['sid']))->find();
                //      $openid=M('personal')->where(array('id'=>$template_sell['uid']))->getfield('openid');
                //      $data=array(
                //         'first'=>array('value'=>urlencode('出售提醒！！！'),'color'=>"#FFA500"),
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
            $this->redirect('Personal/index');
        }else{
            //验证失败

           echo "验证失败";

       }

    }

}

