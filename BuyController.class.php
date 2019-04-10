<?php
namespace Home\Controller;
use Think\Controller;
class BuyController extends BaseController {
    // 我要买
    public function index(){
		
        // 商品类
        $class=M('classify')->select();
        // 商品类下面的所有商品id
        foreach ($class as $key => $value) {
        	$son['cid']=$value['id'];
        	$class[$key]['son']=M('product')->where($son)->getField('id',true);
        }
        // 商品类下面的所有商品下面出售中的数量
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                $pid['pid']=$v;
                $pid['sell_status']=1;
                $pid['sell_overdue_time']=array('egt',date('Y-m-d',time()));
                $class[$key]['son'][$k]=M('sell')->where($pid)->count();
            }
        }
        // 商品类出售中总数量
        foreach ($class as $key => $value) {
            $sold=0;
            foreach ($value['son'] as $k => $v) {
                $sold=$sold+$v;
                $class[$key]['sold']=$sold;
            }
        }

        $this->assign('class',$class);//商品类
        $this->display();
    }
	// 搜索
    public function search(){
        // 模糊搜索条件
        $data=I('post.search_name');
        $search['p.t_title|s.s_title']=array('like',"%$data%");
		$search['p.pro_type']=1;
		$search['p.pro_type2']=1;
        // 搜索
        if(!empty($data)){
            $search_data=M('product')
                ->alias('p')
                ->field('p.*,p.id as pid,s.*')
                ->join('left join tp_source as s on p.sid=s.id')
                ->where($search)
                ->select();
            // 分类
            $class=M('classify')->select();
            foreach ($class as $key => $value) {
                 foreach ($search_data as $k => $v) {
                      if($value['id']==$v['cid']){
                          $class[$key]['son'][]=$v;
						  $pid['pid']=$v['pid'];
						  $pid['sell_status']=1;
						  $pid['sell_overdue_time']=array('egt',date('Y-m-d',time()));
						  $class[$key]['sson'][$k]=M('sell')->where($pid)->count();
                      }
				 }
                
            }
            // 不存在则删除
            foreach ($class as $key => $value) {
                if(!isset($value['son'])){
                    unset($class[$key]);
                }
            }
			// 商品类出售中总数量
			foreach ($class as $key => $value) {
				$sold=0;
				foreach ($value['sson'] as $k => $v) {
					$sold=$sold+$v;
					$class[$key]['sold']=$sold;
				}
			}
        }else{
            // 商品类
			$class=M('classify')->select();
			// 商品类下面的所有商品id
			foreach ($class as $key => $value) {
				$son['cid']=$value['id'];
				$class[$key]['son']=M('product')->where($son)->getField('id',true);
			}
			// 商品类下面的所有商品下面出售中的数量
			foreach ($class as $key => $value) {
				foreach ($value['son'] as $k => $v) {
					$pid['pid']=$v;
					$pid['sell_status']=1;
					$pid['sell_overdue_time']=array('egt',date('Y-m-d',time()));
					$class[$key]['son'][$k]=M('sell')->where($pid)->count();
				}
			}
			// 商品类出售中总数量
			foreach ($class as $key => $value) {
				$sold=0;
				foreach ($value['son'] as $k => $v) {
					$sold=$sold+$v;
					$class[$key]['sold']=$sold;
				}
			}
        }
 
        $this->assign('class',$class);//分类
        $this->assign('search',$data);//搜索字
        $this->display('index');
    }
    // 商品列表
    public function product(){
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        // 浮动系数
        $ratio=$by_laws['ratio'];
        // 倍数B
        $multipleB=$by_laws['multipleB'];
        // 用户等级
        $grande="";
        $uid['id']=session('user_id');
        $personal=M('personal')->where($uid)->find();
        $integral=M('level')->select();
        foreach ($integral as $key => $value) {
            $integral=explode(',',$value['integral']);
            if($personal['score']>=$integral[0]&&$personal['score']<=$integral[1]){
                $grande=$value['title_num'];
            }
        }
        // 浮动价格=（星级-1）*浮动系数
        // $float=($grande-1)*$ratio;
        // 商品类id
        $id['id']=I('id');
        // 商品类
        $classify=M('classify')->where($id)->find();
        // 对应商品类商品
		$pro['pro_type']=1;
		$pro['pro_type2']=1;
		$pro['cid']=$classify['id'];
        //$product=M('product')->where(array('cid'=>$classify['id']))->select();
		$product=M('product')->where($pro)->select();
        // 实际购买价格=定的购买价-浮动价格*倍数B
        foreach ($product as $key => $value) {
            // 浮动价格=差价（星级-1）*浮动系数
            $yprice=$value['t_buy_price']-$value['t_sold_price'];
            $float=$yprice*($grande-1)*$ratio/100;
            // 来源
            $product[$key]['source']=M('source')->where(array('id'=>$value['sid']))->find();
            // 实际购买价格=定的购买价-浮动价格*倍数B
            $product[$key]['fact_price']=sprintf("%.2f",$value['t_buy_price']-$float*$multipleB);
        }
        // 商品下面出售中的数量
        foreach ($product as $key => $value) {
            $pid['pid']=$value['id'];
            $pid['sell_status']=1;
            $pid['sell_overdue_time']=array('egt',date('Y-m-d',time()));
            $product[$key]['son'][]=M('sell')->where($pid)->count();
            $product[$key]['sell_num']=M('sell')->where($pid)->count();
        }
        foreach ($product as $key => $value) {
            $sold=0;
            foreach ($value['son'] as $k => $v) {
                $sold=$sold+$v;
                $product[$key]['sold']=$sold;
            }
        }
        // 商品类出售中总数量
        $total=0;
        foreach ($product as $key => $value) {
            $total=$total+$value['sold'];
        }
        // 是否收藏
        
        $collection['uid']=session('user_id');
        $collection['type']=1;
        $collection=M('collection')->where($collection)->getField('pid',true);
        $collection=implode(',',$collection);
        // 排序
        $product=arraySequence($product,'sell_num');
        
      
   
        
        
        $this->assign('sell',$sell);
        $this->assign('classify',$classify);//商品类
        $this->assign('product',$product);//商品
        $this->assign('total',$total);//商品
        $this->assign('collection',$collection);//是否收藏
        $this->assign('purchase_upper',$by_laws['purchase_upper']);//一次性收购上限
        $this->display();
    }
    // 排序函数
    public function arraySequence($array, $field, $sort = 'SORT_DESC'){
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }
    // 商品详情
    public function productdetails(){
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        // 浮动系数
        $ratio=$by_laws['ratio'];
        // 倍数B
        $multipleB=$by_laws['multipleB'];
        // 用户等级
        $grande="";
        $uid['id']=session('user_id');
        $personal=M('personal')->where($uid)->find();
        $integral=M('level')->select();
        foreach ($integral as $key => $value) {
            $integral=explode(',',$value['integral']);
            if($personal['score']>=$integral[0]&&$personal['score']<=$integral[1]){
                $grande=$value['title_num'];
            }
        }
        // 浮动价格=（星级-1）*浮动系数
        // $float=($grande-1)*$ratio;
        // 商品id
        $id['id']=I('id');
        // 商品信息
        $product=M('product')->where($id)->find();
        // 出售中数量
        $sell['pid']=$product['id'];
        $sell['sell_status']=1;
        $sell['sell_overdue_time']=array('egt',date('Y-m-d',time()));
        $sell=M('sell')->where($sell)->count();
        // 浮动价格=差价（星级-1）*浮动系数
        $yprice=$product['t_buy_price']-$product['t_sold_price'];
        $float=$yprice*($grande-1)*$ratio/100;
        // 浮动后价格
        $fact_price=sprintf("%.2f",$product['t_buy_price']-$float*$multipleB);
        // 来源
        $source['id']=$product['sid'];
        $source=M('source')->where($source)->find();
        //查看备注
        $wh=array(
            'sell_status'=>1,
            'pid'=>I('get.id'),
        );
     
        $remarks=M("sell")->where($wh)->order('id desc')->find();
     




        $this->assign('remarks',$remarks);
        $this->assign('product',$product);// 商品信息
        $this->assign('sell',$sell);// 出售中数量
        $this->assign('fact_price',$fact_price);// 浮动后价格
        $this->assign('source',$source);// 来源
        $this->assign('purchase_upper',$by_laws['purchase_upper']);//一次性收购上限
        $this->display();
    }
    // ajax收藏
    public function collection(){
        $data=I();
        $data['uid']=session('user_id');
        $data['type']=1;
        // 是否已存在
        $isres=M('collection')->where($data)->find();
        if($isres){
           M('collection')->where(array('id'=>$isres['id']))->delete();
           echo 3;die;
        }
        $res=M('collection')->add($data);
        if($res){
            echo 1;die;
        }else{
            echo 2;die;
        }
    }
    // 我的收藏
    public function mycollection(){
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        // 浮动系数
        $ratio=$by_laws['ratio'];
        // 倍数B
        $multipleB=$by_laws['multipleB'];
        // 用户等级
        $grande="";
        $uid['id']=session('user_id');
        $personal=M('personal')->where($uid)->find();
        $integral=M('level')->select();
        foreach ($integral as $key => $value) {
            $integral=explode(',',$value['integral']);
            if($personal['score']>=$integral[0]&&$personal['score']<=$integral[1]){
                $grande=$value['title_num'];
            }
        }
        // 浮动价格=（星级-1）*浮动系数
        // $float=($grande-1)*$ratio;
        //收藏
        $collection['c.uid']=session('user_id');
        $collection['c.type']=1;
		$collection['p.pro_type']=1;
		$collection['p.pro_type2']=1;
        $search_data=M('collection')
                ->alias('c')
                ->join('left join tp_product as p on c.pid=p.id')
                ->join('left join tp_source as s on p.sid=s.id')
                ->where($collection)
                ->select();
        // 分类
        $class=M('classify')->select();
        foreach ($class as $key => $value) {
             foreach ($search_data as $k => $v) {
                  if($value['id']==$v['cid']){
                      $class[$key]['son'][]=$v;
                  }
             }
        }
        // 不存在则删除
        foreach ($class as $key => $value) {
            if(!isset($value['son'])){
                unset($class[$key]);
            }
        }
        // 浮动后价格
        // 实际购买价格=定的购买价-浮动价格*倍数B
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                // 浮动价格=差价（星级-1）*浮动系数
                $yprice=$value['t_buy_price']-$value['t_sold_price'];
                $float=$yprice*($grande-1)*$ratio/100;
                $class[$key]['son'][$k]['fact_price']=sprintf("%.2f",$v['t_buy_price']-$float*$multipleB);
            }
        }
        // 商品类下面的所有商品id
        foreach ($class as $key => $value) {
            $son['cid']=$value['id'];
			$son['pro_type']=1;
			$son['pro_type2']=1;
            $class[$key]['product']=M('product')->where($son)->getField('id',true);
        }
        // 商品类下面的所有商品下面出售中的数量
        foreach ($class as $key => $value) {
            foreach ($value['product'] as $k => $v) {
                $pid['pid']=$v;
                $pid['sell_status']=1;
                $pid['sell_overdue_time']=array('egt',date('Y-m-d',time()));
                $class[$key]['product'][$k]=M('sell')->where($pid)->count();
            }
        }
        // 商品类出售中总数量
        foreach ($class as $key => $value) {
            $sold=0;
            foreach ($value['product'] as $k => $v) {
                $sold=$sold+$v;
                $class[$key]['sold']=$sold;
            }
        }
        
        $this->assign('by_laws',$by_laws);//细则
        $this->assign('class',$class);//分类
        $this->display();
    }
    // 收藏商品列表
    public function collectionlist(){
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        // 浮动系数
        $ratio=$by_laws['ratio'];
        // 倍数B
        $multipleB=$by_laws['multipleB'];
        // 用户等级
        $grande="";
        $uid['id']=session('user_id');
        $personal=M('personal')->where($uid)->find();
        $integral=M('level')->select();
        foreach ($integral as $key => $value) {
            $integral=explode(',',$value['integral']);
            if($personal['score']>=$integral[0]&&$personal['score']<=$integral[1]){
                $grande=$value['title_num'];
            }
        }
        // 浮动价格=（星级-1）*浮动系数
        // $float=($grande-1)*$ratio;
        // 商品类id
        $id['id']=I('id');
        // 商品类
        $classify=M('classify')->where($id)->find();
        // 对应商品类商品
        // $product=M('product')->where(array('cid'=>$classify['id']))->select();
        $wh['c.uid']=session('user_id');
        $wh['c.type']=1;
        $wh['p.cid']=$classify['id'];
		$wh['p.pro_type']=1;
		$wh['p.pro_type2']=1;
        $product=M('product')
                ->alias('p')
                ->field('c.*,p.*')
                ->join('left join tp_collection as c on p.id=c.pid')
                ->where($wh)
                ->select();
        // 实际购买价格=定的购买价-浮动价格*倍数B
        foreach ($product as $key => $value) {
            // 浮动价格=差价（星级-1）*浮动系数
            $yprice=$value['t_buy_price']-$value['t_sold_price'];
            $float=$yprice*($grande-1)*$ratio/100;
            // 来源
            $product[$key]['source']=M('source')->where(array('id'=>$value['sid']))->find();
            // 实际购买价格=定的购买价-浮动价格*倍数B
            $product[$key]['fact_price']=sprintf("%.2f",$value['t_buy_price']-$float*$multipleB);
        }
        // 商品下面出售中的数量
        foreach ($product as $key => $value) {
            $pid['pid']=$value['id'];
            $pid['sell_status']=1;
            $pid['sell_overdue_time']=array('egt',date('Y-m-d',time()));
            $product[$key]['son'][]=M('sell')->where($pid)->count();
        }
        foreach ($product as $key => $value) {
            $sold=0;
            foreach ($value['son'] as $k => $v) {
                $sold=$sold+$v;
                $product[$key]['sold']=$sold;
            }
        }
        // 商品类出售中总数量
        $total=0;
        foreach ($product as $key => $value) {
            $total=$total+$value['sold'];
        }
        // 是否收藏
        $collection['uid']=session('user_id');
        $collection['type']=1;
        $collection=M('collection')->where($collection)->getField('pid',true);
        $collection=implode(',',$collection);
        
        $this->assign('id',I('id'));
        $this->assign('classify',$classify);//商品类
        $this->assign('product',$product);//商品
        $this->assign('total',$total);//商品
        $this->assign('collection',$collection);//是否收藏
        $this->assign('purchase_upper',$by_laws['purchase_upper']);//一次性收购上限
        $this->display();
    }
    // 判断购买上限
    public function buynum(){
        // 判断是否黑名单（8次纠纷）
        $uid['id']=session('user_id');
        $blacklist=M('personal')->field('blacklist')->where($uid)->find();
        if($blacklist['blacklist']>=8){
             echo 4;die;
        }
        // 判断是数字
        $data=I('num');
        if(!preg_match("/^[0-9]*$/",$data)){
            echo 2;die;
        }
        $sell['pid']=I('id');
        $sell['sell_status']=1;
        $sell['sell_overdue_time']=array('egt',date('Y-m-d',time()));
        $number=M('sell')->where($sell)->count();
        if($data>$number){
            echo 3;die;
        }
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        $purchase_upper=$by_laws['purchase_upper'];
        if($data>$purchase_upper||empty($data)){
           echo 2;die;
        }
    }
    // 生成订单
    public function order(){
        $datapost=I();
        // 订单id
        $data['o_number']=date('YmdHis',time()).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
        // 商品id
        $data['pid']=$datapost['id'];
        // 用户id
        $data['uid']=session('user_id');
        // 数量
        $data['num']=$datapost['num'];
        // 单价
        $data['price']=$datapost['fact_price'];
        // 总金额
        $data['sum']=$datapost['num']*$datapost['fact_price'];
        // 下单时间
        $data['time']=time();
        // 订单状态
        $data['status']=0;
        // 生成订单
        $res=M('order')->add($data);
        // 锁定代金券码
        // 优先出售商品有限期最短的，比如在3天内就要过期的，优先卖出，然后在按照上传时间来售出
        $time['pid']=$datapost['id'];
        $time['sell_overdue_time']=array('elt',date("Y-m-d",strtotime("+3 day")));
        $time['sell_status']=1;
        $sell_num=M('sell')->where($time)->count();
        
        // 判断购买数量小于等于3天内就要过期的
        if($sell_num>=$datapost['num']){
            $sell_id=M('sell')->field('id')->order('sell_overdue_time ASC')->where($time)->limit($datapost['num'])->select();
            $locking['oid']=$data['o_number'];
            foreach ($sell_id as $key => $value) {
                $locking['sid']=$value['id'];
                M('order_sell')->add($locking);
                // 锁定
                M('sell')->where(array('id'=>$value['id']))->save(array('sell_status'=>6));
            }
        }else{ // 判断购买数量大于3天内就要过期的
            $differ=$datapost['num']-$sell_num;//相差数量
            $sell_id=M('sell')->field('id')->order('sell_overdue_time ASC')->where($time)->limit($sell_num)->select();
            $locking['oid']=$data['o_number'];
            foreach ($sell_id as $key => $value) {
                $locking['sid']=$value['id'];
                M('order_sell')->add($locking);
                // 锁定
                M('sell')->where(array('id'=>$value['id']))->save(array('sell_status'=>6));
            }
            // 剩下按照上传时间来售出
            $timeout['pid']=$datapost['id'];
            $timeout['sell_status']=1;
            $differlocking=M('sell')->field('id')->order('sell_addtime ASC')->where($timeout)->limit($differ)->select();
            foreach ($differlocking as $key => $value) {
                $locking['sid']=$value['id'];
                M('order_sell')->add($locking);
                // 锁定
                M('sell')->where(array('id'=>$value['id']))->save(array('sell_status'=>6));
            }
        }

        $this ->redirect('Buy/orderdetails',array('number'=>$data['o_number']));
    }
    // 支付页面详情
    public function orderdetails(){
        $data=I();
        $wh['o_number']=$data['number'];
        //订单详情
        $order=M('order')->where($wh)->find();
        if($order){
            // 订单倒计时时间（分）
            // $order_down=M('config')->where(array('id'=>1))->find();
            // 过期时间
            // $order['endtime']=date('Y-m-d H:i:s',$order['time']+60*$order_down['order_down']);
            $order['endtime']=date('Y/m/d H:i:s',$order['time']+6*10);
            
            // 转换
            $order['time']=date('Y/m/d H:i:s',$order['time']);
            // 商品详情
            $product=M('product')->where(array('id'=>$order['pid']))->find();
            $this->assign('order',$order);
            $this->assign('product',$product);
            $this->display();
        }else{
            tips('该订单已失效');
        }
    }
    // 解锁
    public function unlock(){
        $data=I();
        $oid=I('post.o_number');
        
        $order_sell=M('order_sell')->where('oid='.$oid)->select();
       
        if($order_sell){
            foreach ($order_sell as $key => $value) {
            //券回滚
                M('sell')->where(array('id'=>$value['sid']))->save(array('sell_status'=>1));
               
            }
            // 删除订单商品
            M('order_sell')->where($wh)->delete();
            // 删除订单
            $order=M('order')->where(array('o_number'=>$data['o_number']))->find();
            M('order')->where(array('id'=>$order['id']))->delete();
            echo 1;
        }else{
            echo 1;
        }
        
    }
    //监听
    public function listen(){
         // $this->_sock('http://www.bhxcx.cn/qsjy/index.php/Home/Buy/task');
         $url="http://".$_SERVER['SERVER_NAME'].C('m')."/index.php/Home/Buy/task";
         $this->_sock($url);
    }
     // 任务
    public function task(){
        ignore_user_abort(true);
        set_time_limit(0);
        date_default_timezone_set('PRC'); // 切换到中国的时间
        //$run_time = strtotime('+1 day'); // 定时任务第一次执行的时间是明天的这个时候
        $run_time =strtotime('+1 minute'); // 定时任务第一次执行的时间是10分钟的
        //$interval = 3600*12; // 每12个小时执行一次
        $interval = 60; // 每10分钟执行一次
        if(!file_exists('./cron-run')) exit(); // 在目录下存放一个cron-run文件，如果这个文件不存在，说明已经在执行过程中了，该任务就不能再激活，执行第二次，否则这个文件被多次访问的话，服务器就要崩溃掉了
        do {
            if(!file_exists('./cron-switch')) break; // 如果不存在cron-switch这个文件，就停止执行，这是一个开关的作用
            $gmt_time = microtime(true); // 当前的运行时间，精确到0.0001秒
            $loop = isset($loop) && $loop ? $loop : $run_time - $gmt_time; // 这里处理是为了确定还要等多久才开始第一次执行任务，$loop就是要等多久才执行的时间间隔
            $loop = $loop > 0 ? $loop : 0;
            if(!$loop) break; // 如果循环的间隔为零，则停止
            sleep($loop);
            // ...
            // $order['uid']=session('user_id');
            // 订单倒计时时间（分）
            // $order_down=M('config')->where(array('id'=>1))->find();
            $order['status']=0;
            // $order['time']=array('elt',time()-60*$order_down['order_down']);
            $order['time']=array('elt',time()-60*10);
            $order_data=M('order')
                      ->alias('o')
                      ->where($order)
                      ->select();
            foreach ($order_data as $key => $value) {
               $order_data[$key]['son']=M('order_sell')->where(array('oid'=>$value['o_number']))->select();
            }
            foreach ($order_data as $k => $v) {
                foreach ($v['son'] as $key => $value) {
                    M('sell')->where(array('id'=>$value['sid']))->save(array('sell_status'=>1));
                    // 删除订单商品
                    M('order_sell')->where(array('id'=>$value['id']))->delete();
                    M('order')->where(array('id'=>$v['id']))->delete();
                }
            }
            file_put_contents('listen.txt',date('Y-m-d H:i:s'));
            // ...
            rmdir('./cron-run'); // 这里就是通过删除cron-run来告诉程序，这个定时任务已经在执行过程中，不能再执行一个新的同样的任务
            $loop = $interval;
        }while(true);
    }

    // 远程请求（不获取内容）函数
    public function _sock($url) {
        $host = parse_url($url,PHP_URL_HOST);
        $port = parse_url($url,PHP_URL_PORT);
          $port = $port ? $port : 80;
        $scheme = parse_url($url,PHP_URL_SCHEME);
        $path = parse_url($url,PHP_URL_PATH);
        $query = parse_url($url,PHP_URL_QUERY);
        if($query) $path .= '?'.$query;
        if($scheme == 'https') {
          $host = 'ssl://'.$host;
        }
        if($fp = @fsockopen($host,$port,$error_code,$error_msg,5)) {
          stream_set_blocking($fp,0);//开启了手册上说的非阻塞模式
          $header = "GET $path HTTP/1.1\r\n";
          $header.="Host: $host\r\n";
          $header.="Connection: Close\r\n\r\n";//长连接关闭
          fwrite($fp, $header);
          fclose($fp);
        }
        return array($error_code,$error_msg);
    }

}