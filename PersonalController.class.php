<?php
namespace Home\Controller;
use Think\Controller;
class PersonalController extends BaseController {
    // 首页
    public function index(){
		$down=M("config")->where("id=1")->getField("auto_complete");

        $sell_list=M("sell")->where("uid=".$_SESSION["user_id"]." and freeze=1")->select();
               //var_dump($sell_list);    
	    foreach($sell_list As $k=>$v){

	    	//用户使用玩卡后解冻
		    $order=M("order_sell")->where('sid='.$v['id'])->select();
		     foreach($order as $ke=>$va){
		     	if($va['status']==2)
		     	{
			     	M("sell")->where("id=".$va['sid'])->save(array("freeze"=>2));
			     	//获取商品价格
			     	$wh=array(
				     	'uid'=>$_SESSION["user_id"],
				     	'freeze'=>2,
				     	'id'=>$va['sid'],
			     	);
			      	$sell_order=M("sell")->where($wh)
			      			  ->field('price')
			      			  ->select();			      		  
			     	foreach($sell_order as $ky=>$vu){
			     		M("personal")->where("id=".$_SESSION["user_id"])->setInc("cash",$vu["price"]);
						M("personal")->where("id=".$_SESSION["user_id"])->setDec("ncash",$vu["price"]);

			     	}
		     		
		     	}
		     }

		    //纠纷,卖家责任把钱退还给买家
		   //  $seel=M("order_sell")->where('sid='.$v['id'])->select();
		   // /// var_dump($seel);
		   //  foreach ($seel as $key => $valu) {
		   //  	if($valu['status']==4)
		   //  	{
		   //  		//M("sell")->where("id=".$va['sid'])->save(array("freeze"=>2));

		   //  		// $we=array(
				 //    //  	'uid'=>$_SESSION["user_id"],
				 //    //  	'freeze'=>2,
				 //    //  	'id'=>$va['sid'],
			  //    // 	);
			  //    //  	$seel_order=M("sell")->where($we)
			  //    //  			  ->field('price')
			  //    //  			  ->select();	
			     	
			  //    	$aa=$valu['oid'];
			     
			  //    	$we=array(
			  //    		'o_number'=>$aa,
			  //    	);
			  //    	 $seel_order=M("order_sell")->where($we)->field("uid")->select();

			  //    	 var_dump($seel_order);exit;
			  //    	// var_dump($seel_order);exit;
			  //  //    	foreach($seel_order as $o=>$c){
			  //  //    		$ordrt=M("")
			  //  //    		M("personal")->where("id=".$_SESSION["user_id"])->setInc("cash",$vu["price"]);
					// 	// M("personal")->where("id=".$_SESSION["user_id"])->setDec("ncash",$vu["price"]);
			  //  //    	}
		   //  	}
		   //  }

	    	// 点进判断出售时间是否满足解冻
		   if($v["sell_soldtime"]+3600*$down<time() ){
			   M("sell")->where("id=".$v["id"])->save(array("freeze"=>2));
			   M("personal")->where("id=".$_SESSION["user_id"])->setInc("cash",$v["price"]);
			   M("personal")->where("id=".$_SESSION["user_id"])->setDec("ncash",$v["price"]);
			   $pid=M("personal")->where("id=".$_SESSION["user_id"])->getField('parent_id');
			   if($pid!=0){
			   		// 成交加成
			   		$product=M('product')->where(array('id'=>$v['pid']))->find();
			   		//  购买价（买家）- 出售价（卖家） > 0的时候，邀请成交的提成为 提成=【购买价（买家）- 出售价（卖家） 】 * 1%
			   		if($product['t_buy_price']>$product['t_sold_price']){
						$prices=sprintf("%.2f",$product['t_buy_price']-$product['t_sold_price']);
			   			M("push")->add(array("son_id"=>$_SESSION["user_id"],"parent_id"=>$pid,"type"=>"2","commission"=>sprintf("%.2f",$prices*0.01),"time"=>time()));
						M("personal")->where("id=".$pid)->setInc("cash",sprintf("%.2f",$prices*0.01));
			   		}
			    }
		    }
	    }
		//冻结金额
        $frozen=M("sell")->where("uid=".$_SESSION["user_id"]." and freeze=1")->sum('price');
		$frozen=$frozen?$frozen:'0.00';
		M("personal")->where("id=".$_SESSION["user_id"])->save(array('ncash'=>$frozen));

		$this->assign('frozen',$frozen);
		//购买节省
		$mysum=M("order")->where("uid=".$_SESSION["user_id"])->sum('sum');
		$buysell=M("order")
				->alias('o')
				->join('left join tp_order_sell as os on o.o_number=os.oid')
				->join('left join tp_sell as s on os.sid=s.id')
				->join('left join tp_product as p on s.pid=p.id')
				->where("o.uid=".$_SESSION["user_id"])
				->sum('t_buy_price');
	    $cprice=sprintf("%.2f",$buysell-$mysum);
		$this->assign('cprice',$cprice);
		
		//统计出售中条数
		$s_status1=M("sell")->where("uid=".$_SESSION["user_id"]." and sell_status=1")->count();
		$s_status3=M("sell")->where("uid=".$_SESSION["user_id"]." and sell_status=3")->count();
		$s_status2=M("sell")->where("uid=".$_SESSION["user_id"]." and sell_status=2")->count();
		$s_status4=M("sell")->where("uid=".$_SESSION["user_id"]." and sell_status=4")->count();
		//统计购买的条数
		$b_status0=M("order")->where("uid=".$_SESSION["user_id"]." and status=0")->count();
		// 我的所有订单
		// 待使用
		$uisd1['o.uid']=$_SESSION["user_id"];
		$uisd1['os.status']=1;
		$order_count1=M('order')
					->alias('o')
					->join('left join tp_order_sell as os on o.o_number=os.oid')
			        ->where($uisd1)
			        ->select();
		// 已完成
		$uisd2['o.uid']=$_SESSION["user_id"];
		$uisd2['os.status']=2;
		$order_count2=M('order')
					->alias('o')
					->join('left join tp_order_sell as os on o.o_number=os.oid')
			        ->where($uisd2)
			        ->select();
		// 纠纷数量
		$uisd3['o.uid']=$_SESSION["user_id"];
		$uisd3['os.status']=3;
		$order_count3=M('order')
					->alias('o')
					->join('left join tp_order_sell as os on o.o_number=os.oid')
			        ->where($uisd3)
			        ->select();
		$b_status1=count($order_count1);
		$b_status2=count($order_count2);
		$b_status3=count($order_count3);
		// $b_status1=M("order")->where("uid=".$_SESSION["user_id"]." and status=1")->count();
		// $b_status2=M("order")->where("uid=".$_SESSION["user_id"]." and status=2")->count();
		// $b_status3=M("order")->where("uid=".$_SESSION["user_id"]." and status=3")->count();
		$this->assign("s_status1",$s_status1);
		$this->assign("s_status3",$s_status3);
		$this->assign("s_status2",$s_status2);
		$this->assign("s_status4",$s_status4);
		$this->assign("b_status0",$b_status0);
		$this->assign("b_status1",$b_status1);
		$this->assign("b_status2",$b_status2);
		$this->assign("b_status3",$b_status3);
		$personal=M("personal")->find($_SESSION["user_id"]);
        // 用户等级
        $grande="";
        $integral=M('level')->select();
        foreach ($integral as $key => $value) {
            $integral=explode(',',$value['integral']);
            if($personal['score']>=$integral[0]&&$personal['score']<=$integral[1]){
                $grande=$value['title'];
            }
        }
        // 我的成就(我的卖单)
        $seller=[];
        $achieve['uid']=session('user_id');
        $achieve['sell_status']=array('in',"2,4");
        // 出售单数（包括有纠纷的）
        $seller['number']=M('sell')->where($achieve)->count();
        $seller['number']=$seller['number']?$seller['number']:0;
        // 出售信誉＝成功出售次数除以总出售次数（包括有纠纷的次数）
        $reputation['uid']=session('user_id');
        $reputation['sell_status']=array('in',"2");
        // 成功出售次数
        $seller['snumber']=M('sell')->where($reputation)->count();
		$seller['snumber']=$seller['snumber']?$seller['snumber']:0;
        // 总出售次数（包括有纠纷的次数）
        // $reputation2['uid']=session('user_id');
        // $reputation2['sell_status']=array('in',"2,4");
        $seller['anumber']=M('sell')->where($achieve)->count();
		$seller['anumber']=$seller['anumber']?$seller['anumber']:0;
        // 出售信誉
        $sell_number=$seller['snumber']/$seller['anumber']*100;
        $seller['xy']=sprintf("%.2f", $sell_number);
        // 共出售（包括有纠纷的次数）
        $seller['total']=M('sell')->where($achieve)->sum('price');
		$seller['total']=$seller['total']?$seller['total']:0;
        // 出售盈利就统计总的出售金额，无需额外别的数据
        $seller['profit']=M('sell')->where($achieve)->sum('price');
		$seller['profit']=$seller['profit']?$seller['profit']:0;
        // 我的成就(我的买单)
        $buy=[];
        $order['uid']=session('user_id');
        $order['status']=array('in',"1,2,3");
        // 购买单数（包括有纠纷的）
        $buy['number']=M('order')->where($order)->sum('num');
        $buy['number']=$buy['number']?$buy['number']:0;
        // 购买信誉＝成功交易购买次数除以总的购买次数（包括有纠纷的）
        $order_reputation['uid']=session('user_id');
        $order_reputation['status']=array('in',"1,2");
        // 成功交易购买次数
        $buy['snumber']=M('order')->where($order_reputation)->sum('num');
		$buy['snumber']=$buy['snumber']?$buy['snumber']:0;
        // 总的购买次数（包括有纠纷的）
        // $order_reputation2['uid']=session('user_id');
        // $order_reputation2['status']=array('in',"1,2,3");
        $buy['anumber']=M('order')->where($order)->sum('num');
		$buy['anumber']=$buy['anumber']?$buy['anumber']:0;
        // 购买信誉
        $order_number=$buy['snumber']/$buy['anumber']*100;
        $buy['xy']=sprintf("%.2f", $order_number);
        // 共购买（包括有纠纷的次数）
        $buy['total']=M('order')->where($order)->sum('sum');
		$buy['total']=$buy['total']?$buy['total']:0;
		
		$this->assign("seller",$seller);
		$this->assign("buy",$buy);
	    $this->assign("personal",$personal);
	    $this->assign("grande",$grande);
        $this->display();
    }
	//钱包
	public function money(){
		$personal=M("personal")->find($_SESSION["user_
			id"]);
		if(!empty($personal["name"])){
			$this->assign("isexist",1);
		}else{
			$this->assign("isexist",0);
		}
		//冻结金额
        $frozen=M("sell")->where("uid=".$_SESSION["user_id"]." and freeze=1")->sum('price');
		$frozen=$frozen?$frozen:'0.00';
		M("personal")->where("id=".$_SESSION["user_id"])->save(array('ncash'=>$frozen));
		
		$this->assign("personal",$personal);
		$this->assign("frozen",$frozen);
		$this->display();
	}
	//我的买单
	public function buy(){
		$wh["status"]=I('get.status');
		$wh["uid"]=$_SESSION["user_id"];
		$down=M("config")->where("id=1")->getField("count_down");
		$list=M("order")->where("uid=".$_SESSION["user_id"]." and status=1")->select();
		foreach($list As $k=>$v){
			 if($v["paytime"]+3600*$down<time()){
			//if($v["paytime"]+180<time()){
				// M("order")->where("id=".$v["id"])->save(array("status"=>2));
				// 订单产品改状态
				$order_sell=M('order_sell')->where(array('oid'=>$v['o_number']))->select();
				foreach ($order_sell as $key => $value) {
					if($value['status']==1){
						M("order_sell")->where("id=".$value["id"])->save(array("status"=>2));
					}
				}
			 }
		}
		// 支付中的数据过期删除
		$out_time=M("order")->where("uid=".$_SESSION["user_id"]." and status=0")->select();
		foreach($out_time As $k=>$v){
			 if($v["time"]+600<time()){
				M("order")->where("id=".$v["id"])->delete();
				M('order_sell')->where("oid=".$v["o_number"])->delete();
			 }
		}
		// 查找数据
		// $list=M("order")
		// 	->alias("o")
		// 	->field("o.*,o.id as oid,p.t_title,s.s_title,p.t_img")
		// 	->join('left join tp_product as p on o.pid=p.id')
		// 	->join('left join tp_source as s on p.sid=s.id')
		// 	->where($wh)
		// 	->order("id desc")
		// 	->select();
		if(I('get.status')==0){
			$wh2["o.status"]=I('get.status');
			$list=M("order")
			->alias("o")
			->field("o.*,o.id as oid,p.t_title,s.s_title,p.t_img")
			->join('left join tp_product as p on o.pid=p.id')
			->join('left join tp_source as s on p.sid=s.id')
			->where($wh)
			->order('o.paytime desc')
			->select();
		}else if(I('get.status')!=0){
			$wh2["os.status"]=I('get.status');
			$wh2["o.uid"]=$_SESSION["user_id"];
			$list=M("order_sell")
				->alias("os")
				->field("o.*,os.id as oid,os.status as ostatus,os.handle as oshandle,os.duty as osduty,p.t_title,s.s_title,p.t_img")
				->join('left join tp_order as o on o.o_number=os.oid')
				->join('left join tp_product as p on o.pid=p.id')
				->join('left join tp_source as s on p.sid=s.id')
				->order('o.paytime desc')
				->where($wh2)
				->select();
		}
		
		foreach ($list as $key => $value) {
			if($value['status']==0){
				$vaild_pay=$value['time']+600;
				$list[$key]['vaild_pay']=date('H:i',$vaild_pay);
			}else{
				$list[$key]['fill_time']=$value["paytime"]+3600*$down;
			}
		}
		$this->assign("list",$list);
		
		$this->display();
	}
	//买单详情
	public function buy_del(){
		// 订单产品id
		$wh["id"]=I('get.id');
		// 订单产品
		$order_sell=M('order_sell')->where($wh)->find();

		//查询备注
		$sell=M("sell")->where('id='.$order_sell['sid'])->find();	

		
		// 订单数据
		// $re=M("order")->find($wh["id"]);
		$wh2['o_number']=$order_sell['oid'];
		$re=M("order")->where($wh2)->find();
		$re['osid']=I('get.id');

		$product=M("product")->find($re["pid"]);
		$re["t_title"]=$product["t_title"];
		$re["t_market_price"]=$product["t_market_price"];
		$re["t_prompt"]=$product["t_prompt"];
		$re["t_introduce"]=$product["t_introduce"];
		$re["t_makeed"]=$product["t_makeed"];
		$re["t_extent"]=$product["t_extent"];
		$source=M("source")->find($product["sid"]);
		$re["s_title"]=$source["s_title"];
		// 出售商品信息
		$wh3['id']=$order_sell['sid'];
		$re["sell_list"][]=M("sell")->where($wh3)->find();
		
		$re['order_sell']=$order_sell;
		// $sell_list=M("order_sell")->where("oid=".$re["o_number"])->select();
		// foreach($sell_list As $k=>$v){
		// 	$re["sell_list"][$k]=M("sell")->find($v["sid"]);
		// }
		$this->assign('sell',$sell);
		$this->assign("re",$re);
		$this->display();
	}
	//我的卖单
	public function sell(){
		$wh["sell_status"]=I('get.sell_status');
		if($wh["sell_status"]==1){
			$wh["sell_status"]=array('in','0,1,6');
		}
		$wh["uid"]=$_SESSION["user_id"];
		$list=M("sell")
			->alias("s")
			->field("s.*,s.id as ssid,p.t_title,p.t_sold_price,a.s_title,p.t_img")
			->join('left join tp_product as p on s.pid=p.id')
			->join('left join tp_source as a on p.sid=a.id')
			->order('s.sell_soldtime desc')
			->where($wh)
			->select();

		foreach ($list as $key => $value) {
			if($value['sell_status']==4){
				$order_sell=M('order_sell')->where(array('sid'=>$value['id']))->find();
				$list[$key]['handle']=$order_sell['handle'];
				$list[$key]['duty']=$order_sell['duty'];
			}
		}
		/* foreach ($list as $key => $value) {
			if($value['sell_status']==4){
				$order_sell=M('order_sell')->field('oid')->where(array('sid'=>$value['id']))->find();
				$order=M('order')->where(array('o_number'=>$order_sell['oid']))->find();
				$list[$key]['handle']=$order['handle'];
				$list[$key]['duty']=$order['duty'];
			}
		} */
		//p($list);
		$this->assign("list",$list);
		$this->display();
	}
	//买单详情
	public function sell_del(){
		$wh["id"]=I('get.id');
		$re=M("sell")->find($wh["id"]);
		$product=M("product")->find($re["pid"]);
		$re["t_title"]=$product["t_title"];
		$re["t_market_price"]=$product["t_market_price"];
		$re["t_prompt2"]=$product["t_prompt2"];
		$re["t_introduce"]=$product["t_introduce"];
		$re["t_makeed"]=$product["t_makeed"];
		$re["t_extent"]=$product["t_extent"];
		$source=M("source")->find($product["sid"]);
		$re["s_title"]=$source["s_title"];
		// 纠纷状态
		if($re['sell_status']==4){
			$order_sell=M('order_sell')->field('oid')->where(array('sid'=>I('get.id')))->find();
			$order=M('order')->where(array('o_number'=>$order_sell['oid']))->find();
			$re['handle']=$order['handle'];
			$re['duty']=$order['duty'];
		}
		// print_r($re);exit;
		$id=I('get.id');

		$er=array(
			'id'=>$id
		);
		$remarks=M("sell")->where($er)->getField('sell_remarks');
		
		$this->assign("remarks",$remarks);
		$this->assign("re",$re);
		$this->display();
	}
	//绑定支付宝
	public function bind(){
		if(IS_POST){
			$data=I('post.');
			$data["id"]=$_SESSION["user_id"];
			// 绑定是否已经绑定
			$bing=M('personal')->where(array('id'=>$data["id"]))->getField('name');
			if($bing){
				echo 3;die;
			}

			$re=M("personal")->save($data);
			if($re){
				// tips("绑定成功！",U('money'));
				echo 1;die;
			}else{
				// tips('绑定失败');
				echo 2;die;
			}
		}
		
		$this->display();
	}
	//提现明细
	public function profit(){
		$list=M("withdrawals")->where("uid=".$_SESSION["user_id"])->order("id desc")->select();
		$this->assign("list",$list);
		$this->display();
	}
	//提现
	public function transaction(){
		if(IS_POST){
			$data=I('post.');
			$data["type"]=I('type');
			if($data["type"]==1){
				$alipay=M("personal")->where("id=".$_SESSION["user_id"])->find();
				if(!empty($alipay["number"])){
					$data["alipay"]=$alipay["number"];
					$data["alipay_name"]=$alipay["name"];
				}else{
					// tips("请先绑定支付宝账户！",U('Personal/money'));exit;
					echo 3;die;
				}
				
			}
			// 判断今天是否已提现过，一天只能提现一次
			$t = time();
			$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
			$end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
			$wh['w_time']=array('between',"$start,$end");
			$wh['uid']=session('user_id');
			$withdraw=M('withdrawals')->where($wh)->find();
			if($withdraw){
				// tips("今天已经提现一次！",U('Personal/money'));exit;
				echo 4;die;
			}

			$data["uid"]=$_SESSION["user_id"];
			$data["w_addtime"]=date("Y-m-d H:i:s");
			$data["w_time"]=time();
			$data["alipay_status"]=0;
			$re=M("withdrawals")->add($data);
			if($re){
				M("personal")->where("id=".$_SESSION["user_id"])->setDec("cash",$data["price"]);
				// tips("提交成功！",U('money'));
				echo 1;die;
			}else{
				// tips("提交失败！");
				echo 2;die;
			}
		}
		$personal=M("personal")->where("id=".$_SESSION["user_id"])->find();
		$this->assign("personal",$personal);
		$this->display();
	}
	//分享推荐
	public function share(){
		$uid=session('user_id');
		// 判断二维码是否过期
		$scode=M('personal')->where(array('id'=>$uid))->find();
		if($scode['tem_time_out']<=time()){//过期重新去获取
            //微信带参数的二维码
	        $token = getToken();
	        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token;
	        $data = '{"expire_seconds": 2592000, "action_name": "QR_SCENE","action_info": {"scene": {"scene_id": '.$uid.'}}}';
	        $file_contents = curl($url,$data);
	        $url = json_decode($file_contents);
	        $res = get_object_vars($url);
	        $ticket=UrlEncode($res['ticket']);
	        //通过ticket来换取二维码
		    $ticket = urlencode($ticket);
		    $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
		    $file = file_get_contents($url);
		    //我把给二维码文件保存到文件系统
		    $file_name = "qrcode{$uid}.jpg";
		    file_put_contents("./Public/qrcode/". $file_name, $file);
		    // // 把临时二位码和过期时间存放数据库
		    $wh['id']=session('user_id');
		    $code['tem_code']="/Public/qrcode/". $file_name;
		    $code['tem_time_out']=time()+604800;
		    M('personal')->where($wh)->save($code);
		    $qrcode_url = 'http://'.$_SERVER['HTTP_HOST'].C('m').'/Public/qrcode/'.$file_name;

	        $this->assign('qrcode_url',$qrcode_url);
	        $this->assign('url',$res['url']);
	        $this->assign('ticket',$res['ticket']);
		}else{
			$qrcode_url = 'http://'.$_SERVER['HTTP_HOST'].C('m').$scode['tem_code'];
			$this->assign('qrcode_url',$qrcode_url);
		}

		//分享接口配置
        $wxconfig = wx_share_init();

        $this->assign('wxconfig', $wxconfig);
		$this->display();
	}
	//推荐列表
	public function recommend(){
		$type=I('type');
		if($type==1){
			// $list=M("personal")->where("parent_id=".$_SESSION["user_id"])->order("id desc")->select();
			// foreach($list as $k=>$v){
			// 	  $list[$k]['nickname']=base64_decode($v['nickname']);
			// }
			$wh['s.parent_id']=$_SESSION["user_id"];
			$wh['s.type']=1;
			$list=M("push")
				->alias("s")
				->field("s.*,p.username")
				->join('left join tp_personal as p on s.son_id=p.id')
				->where($wh)
				->order("id desc")
				->select();
		}else{
			$wh['s.parent_id']=$_SESSION["user_id"];
			$wh['s.type']=2;
			$list=M("push")
				->alias("s")
				->field("s.*,p.username")
				->join('left join tp_personal as p on s.son_id=p.id')
				->where($wh)
				->order("id desc")
				->select();
				
		}
		$this->assign("list",$list);
		$this->display();
	}
	//冻结明细
	public function freeze_profit(){
		$list=M("sell")->where("uid=".$_SESSION["user_id"]." and freeze=1")->order("id desc")->select();
	
		foreach($list As $k=>$v){
			$pro=M("product")->find($v["pid"]);
			$list[$k]["t_title"]=$pro["t_title"];
		}
		$this->assign("list",$list);
		$this->display();
	}
	//ajax修改用户昵称
	public function ajaxChangeUsername(){
		$data["username"]=I('post.username');
		$data["id"]=$_SESSION["user_id"];
		// 查找是否存在一条
		$wh['id']=array('neq',$_SESSION["user_id"]);
		$wh['username']=$data["username"];
		$isexist=M("personal")->where($wh)->find();
		if($isexist){
			echo 2;exit;
		}else{
			$re=M("personal")->save($data);
			if($re){
				echo 1;exit;
			}else{
				echo 0;exit;
			}
		}
	}
	//ajax把订单状态改为纠纷
	public function ajaxHandleissue(){
		// 订单产品id
		$order_sellid["id"]=I('post.id');
		$order_sell=M('order_sell')->where($order_sellid)->find();
		//$orders=M('order')->where(array('o_number'=>$order_sell['oid']))->find();
		$data['id']=$order_sellid['id'];

		$data["status"]=3;
		$data["handle"]=0;
		$re=M("order_sell")->save($data);
		if($re){
			// 订单产品
			M("sell")->where("id=".$order_sell["sid"])->save(array("sell_status"=>4));
			// $order=M("order")->find($data["id"]);
			// $sell_list=M("order_sell")->where("oid=".$order["o_number"])->select();
			// foreach($sell_list as $k=>$v){
			// 	M("sell")->where("id=".$v["sid"])->save(array("sell_status"=>4));
			// }
			echo 1;exit;
		}else{
			echo 0;exit;
		}
	}
	//ajax修改订单状态为完成
	public function ajaxChangestatus(){
		// 订单产品id
		$order_sellid['id']=I('post.id');
		// $order_sell=M('order_sell')->where($order_sellid)->find();
		// $order=M('order')->where(array('oid'=>$order_sell['o_number']))->find();
		// $data["id"]=I('post.id');
		// $data["status"]=2;
		// $re=M("order")->save($data);
		// 订单产品
		$wh2['status']=2;
		$re=M("order_sell")->where($order_sellid)->save($wh2);
		if($re){
			echo 1;exit;
		}else{
			echo 0;exit;
		}
	}
	//ajax下架sell
	public function ajaxchangeSellStatus(){

		$data=array(
			'id'=>I('post.id'),
			'sell_status'=>I('post.status'),
		);

				
		if($data["sell_status"]==1){//上架
			$theone=M("sell")->find($data["id"]);
			


			// 判断上传产品是否达到上限
			//商品上传上限
			$product=M('product')->where(array('id'=>$theone['pid']))->find();
	

			// 上传的数量（审核中和出售中）
			$sell_w['sell_status']=array('in','1,2');
			$sell_w['pid']=$theone['pid'];
			$sell_num=M('sell')->where($sell_w)->count();

			// 已达上限
			// $data=date("Y-m-d");
			// var_dump($data);
			// $time=$theone["sell_overdue_time"];
			// var_dump($time);
			// $mdate=$time-$data;
			// var_dump($mdate);exit;
			if($sell_num>=$product['sell_limit']){
				echo 4;exit;
			}


			// 判断是否过期
			if($theone["sell_overdue_time"]<date("Y-m-d")){//过期
				M("sell")->where("id=".$data["id"])->delete();
				echo 2;exit;
			}

			//判断后台是否勾选有效期
			
			if($product['t_status'] == 1){
					// 商品的有效期
				$sell_time=$theone['sell_overdue_time'];
				// 产品有效天数
				$vail_time=date('Y-m-d',time());
				$startdate=strtotime($vail_time);
				
				$enddate=strtotime($sell_time);
				
				$days=round(($enddate-$startdate)/3600/24);

				$time=$product['valid_time'];
				// var_dump($days);exit;

				if($days<$product['valid_time']){
					
					echo 3;exit;
				}
			}
			
			$re=M("sell")->save($data);

			if($re){
				echo 1;exit;
			}else{
				echo 0;exit;
			}
		}else{//下架
			$re=M("sell")->save($data);
			if($re){
				echo 5;exit;
			}else{
				echo 6;exit;
			}
		}
	}
	public function ajaxDelSell(){
		$data["id"]=I('post.id');
		$re=M("sell")->where("id=".$data["id"])->delete();
		if($re){
			echo 1;exit;
		}else{
			echo 0;exit;
		}
	}
	
}