<?php
namespace Home\Controller;
use Think\Controller;
class SellerController extends BaseController {
    // 我要卖
    public function index(){
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        // 浮动系数
        $ratio=$by_laws['ratio'];
        // 倍数A
        $multipleA=$by_laws['multipleA'];
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
        // 分类
        $class=M('classify')->select();
        foreach ($class as $key => $value) {
        	$son['p.cid']=$value['id'];
			//$son['pro_type']=1;
        	$class[$key]['son']=M('product')
        	                   ->alias('p')
                               ->field('p.*,p.id as pid,s.*')
        	                   ->join('left join tp_source as s on p.sid=s.id')
        	                   ->where($son)
        	                   ->select();

                               
        	//统计数量
        	$class[$key]['total']=M('product')->where($son)->count();

        }

        //判读时间是否过期
        foreach ($class  as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                $pric=$v['endtime'];
                $endtime=strtotime($pric);
                $time=date('Y-m-d H:i:s',time());
                $times=strtotime($time);
                if($times>$endtime)
                {
                  $wh=array(
                    'id'=>$v['pid'],
                    'expiry_time'=>1,
                  );
                 $product=M("product")->save($wh);
                
                }
            }
        }
        // 浮动后价格
        // 实际出售价格=定的出售价+浮动价格*倍数A
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                // 浮动价格=差价（星级-1）*浮动系数
                $yprice=$v['t_buy_price']-$v['t_sold_price'];
                $float=$yprice*($grande-1)*$ratio/100;
                $class[$key]['son'][$k]['fact_price']=sprintf("%.2f",$v['t_sold_price']+$float*$multipleA);
            }
        }
        // 是否出售上限
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                $pid['pid']=$v['pid'];
                $pid['sell_status']=array('in',"0,1");
                $class[$key]['son'][$k]['up']=M('sell')->where($pid)->count();
            }
        }


        // 是否收藏
        $collection['uid']=session('user_id');
        $collection['type']=0;
        $collection=M('collection')->where($collection)->getField('pid',true);
        $collection=implode(',',$collection);

        $this->assign('by_laws',$by_laws);//细则
        $this->assign('class',$class);//分类
        $this->assign('search',"");//搜索字
        $this->assign('collection',$collection);//收藏
        $this->display();
    }
    // 搜索
    public function search(){
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        // 浮动系数
        $ratio=$by_laws['ratio'];
        // 倍数A
        $multipleA=$by_laws['multipleA'];
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
        // 模糊搜索条件
        $data=I('post.search_name');
        $search['p.t_title|s.s_title']=array('like',"$data%");
		$search['pro_type']=1;
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
                      }
                 }
            }
            // 不存在则删除
            foreach ($class as $key => $value) {
                if(!isset($value['son'])){
                    unset($class[$key]);
                }
            }
        }else{
            // 分类
            $class=M('classify')->select();
            foreach ($class as $key => $value) {
                $son['p.cid']=$value['id'];
                $class[$key]['son']=M('product')
                                   ->alias('p')
                                   ->field('p.*,p.id as pid,s.*')
                                   ->join('left join tp_source as s on p.sid=s.id')
                                   ->where($son)
                                   ->select();
                //统计数量
                $class[$key]['total']=M('product')->where($son)->count();
            }
        }
        // 浮动后价格
        // 实际出售价格=定的出售价+浮动价格*倍数A
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                // 浮动价格=差价（星级-1）*浮动系数
                $yprice=$v['t_buy_price']-$v['t_sold_price'];
                $float=$yprice*($grande-1)*$ratio/100;
                $class[$key]['son'][$k]['fact_price']=sprintf("%.2f",$v['t_sold_price']+$float*$multipleA);
            }
        }
        // 是否出售上限
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                $pid['pid']=$v['pid'];
                $pid['sell_status']=array('in',"0,1");
                $class[$key]['son'][$k]['up']=M('sell')->where($pid)->count();
            }
        }
        // 是否收藏
        $collection['uid']=session('user_id');
        $collection['type']=0;
        $collection=M('collection')->where($collection)->getField('pid',true);
        $collection=implode(',',$collection);
        
        $this->assign('by_laws',$by_laws);//细则
        $this->assign('class',$class);//分类
        $this->assign('search',$data);//搜索字
        $this->assign('collection',$collection);//收藏
        $this->display('index');
    }
    // ajax信用值是否满足上传
    public function limits(){
        $data['id']=I('sell_limit');
        $uid['id']=session('user_id');
        // 是否黑名单8八次
        $blacklist=M('personal')->where($uid)->find();
        if($blacklist['blacklist']>=8){
            echo 3;die;
        }
        // 信用值
        $score=M('personal')->field('score')->where($uid)->find();
        // 找到当前产品
        $sell=M('product')->field('grade_limit')->where($data)->find();
        // 判断是否满足上传
        if($score['score']>=$sell['grade_limit']){
            echo 1;die;
        }else{
            echo 2;die;
        }
    }
    // ajax收藏
    public function collection(){
        $data=I();
        $data['uid']=session('user_id');
        $data['type']=0;
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
	// ajax券码上限
    public function nums(){
        $data=I();
        // 是否已存在
		$product=M('product')->where(array('id'=>$data['pid']))->find();
        if($data['len']>=$product['pro_most_num']){
			$arr['recode']=20000;
			$arr['num']=$product['pro_most_num'];
            echo json_encode($arr);die;
        }else{
			$arr['recode']=40000;
            echo json_encode($arr);die;
        }
    }
    // 我的收藏
    public function mycollection(){
        // 细则
        $by['id']=1;
        $by_laws=M('config')->where($by)->find();
        // 浮动系数
        $ratio=$by_laws['ratio'];
        // 倍数A
        $multipleA=$by_laws['multipleA'];
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
        $collection['c.type']=0;
		$collection['p.pro_type']=1;
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
        // 实际出售价格=定的出售价+浮动价格*倍数A
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                // 浮动价格=差价（星级-1）*浮动系数
                $yprice=$v['t_buy_price']-$v['t_sold_price'];
                $float=$yprice*($grande-1)*$ratio/100;
                $class[$key]['son'][$k]['fact_price']=sprintf("%.2f",$v['t_sold_price']+$float*$multipleA);
            }
        }
        // 是否出售上限
        foreach ($class as $key => $value) {
            foreach ($value['son'] as $k => $v) {
                $pid['pid']=$v['pid'];
                $pid['sell_status']=array('in',"0,1");
                $class[$key]['son'][$k]['up']=M('sell')->where($pid)->count();
            }
        }

        $this->assign('by_laws',$by_laws);//细则
        $this->assign('class',$class);//分类
        //$this->assign('collection',$collection);//收藏
        $this->display();
    }
    // 发布
    public function release(){
        // 立即发布
        // if($_POST){
        if(IS_AJAX){
            $data=I();
            

            $data['uid']=session('user_id');
            $data['sell_addtime']=date('Y-m-d H:i:s',time());
            if(empty(I('sell_overdue_time'))){
                $data['sell_overdue_time']="2099-12-31";
            }
            // 判断来源和兑换码是否已存在
            $seller=M('sell')->where(array('sid'=>$data['sid'],'sell_pass_code'=>$data['sell_pass_code']))->find();
           
            if($seller){
                $arr['code']=2500;
                $this->ajaxreturn($arr);
                // tips('已存在',U('Seller/index'));
            }
        
            //判断是否勾选有效期
           
            
            //商品信息
            $prodata=M('product')->where(array('id'=>I('pid')))->find();

			//判断是否已达上限
			$wps['sell_status']=array('in','0,1');
			$wps['pid']=$data['pid'];
			$wps['sid']=$data['sid'];
			$count=M('sell')->where($wps)->count();
			if($count>=$prodata['sell_limit']){
				$arr['code']=3000;
                $this->ajaxreturn($arr);
			}
            
            $product=M("product")->where('id='.$data['pid'])->getField('t_status');
            if($product['t_status']==0)
            {
                $res=M('sell')->add($data);
                if($res!==flase){
                $arr['code']=1000;
                $this->ajaxreturn($arr);
                // tips('发布成功',U('Seller/index'));
                }

            }
			// 判断日期有效期
            $sell_time=I('sell_overdue_time');

            $sell_time=strtotime($sell_time);
            //var_dump($sell_time);
            $sell_timeimg=date('Y-m-d',time());

            $sell_timeimg=strtotime($sell_timeimg);
              //var_dump($sell_timeimg);exit;
            $Days=round(($sell_time-$sell_timeimg)/3600/24);
            
            if($Days<$prodata['valid_time']){
                $arr['code']=4000;
                $arr['valid_time']=$prodata['valid_time'];

                $this->ajaxreturn($arr);
                // tips("有效期应该大于".$prodata['valid_time']."天");
            }

            // 如果有二维码
            if($data['sell_code']!=''){
                $media_id =$data["sell_code"];
                $access_token =getToken();
                $path='./Public/Uploads/'.date('Y-m-d',time()).'/';
                if(!is_dir($path)){
                    mkdir($path);
                }
                $filename ="wx".time().rand(1111,9999).'.jpg';
                $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media_id}";
                $pathf=$this->downAndSaveFile($url,$path."/".$filename);
                $data['sell_code']=C('m').'/'.$path.$filename;
                // var_dump($data);
            }
             // $this->ajaxreturn($data);
            $res=M('sell')->add($data);

            if($res!==flase){
                $arr['code']=2000;
                $this->ajaxreturn($arr);
                // tips('发布成功',U('Seller/index'));
            }


        }
        // 获取商品id
        $pid['p.id']=I('pid');
        // 细则
        $by['id']=1;
        $release_laws=M('config')->where($by)->find();
        // 商品类别
        $class=M('classify')->select();
        // 商品来源
        $source=M('source')->select();
        // 商品
        $product=M('product')
                ->alias('p')
                ->field('p.*,c.c_title,s.s_title')
                ->join('left join tp_classify as c on p.cid=c.id')
                ->join('left join tp_source as s on p.sid=s.id')
                ->where($pid)
                ->find();
       
        // 浮动系数
        $ratio=$release_laws['ratio'];
        // 倍数A
        $multipleA=$release_laws['multipleA'];
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
        // 浮动价格=差价（星级-1）*浮动系数
        $yprice=$product['t_buy_price']-$product['t_sold_price'];
        $float=$yprice*($grande-1)*$ratio/100;
        // 浮动后价格
        // 实际出售价格=定的出售价+浮动价格*倍数A
        $product['fact_price']=sprintf("%.2f",$product['t_sold_price']+$float*$multipleA);
        // 判断日期有效期
        $prodata=M('product')->where(array('id'=>I('pid')))->find();
        // 是否黑名单8八次
        $blacklist=M('personal')->where($uid)->find();
        if($blacklist['blacklist']>=8){
            $back=1;
            $this->assign('back',$back);
        }
        // 获取jssdk配置
        vendor('Wxshare.jssdk#class');
        $jssdk =new \JSSDK(C('appid'),C('secret'));
        $GetSignPackage =$jssdk->GetSignPackage();

       
        $this->assign('release_laws',$release_laws);//细则
        $this->assign('class',$class);//商品类别
        $this->assign('source',$source);//商品来源
        $this->assign('product',$product);//商品
        $this->assign('time',date('Y-m-d',strtotime("+$prodata[valid_time] day")));//时间
        $this->assign('GetSignPackage',$GetSignPackage);
        $this->assign('nowtime',date('Y-m-d',time()));
        $this->display();
    }
    public function blacklist(){
        $uid['id']=session('user_id');
        $blacklist=M('personal')->where($uid)->find();
        if($blacklist['blacklist']>=8){
            echo 2;
        }else{
            echo 1;
        }
    }
    //根据URL地址，下载文件
    function downAndSaveFile($url,$savePath){
        ob_start();
        readfile($url);
        $img  = ob_get_contents();
        ob_end_clean();
        $size = strlen($img);
        $fp = fopen($savePath, 'a');
        fwrite($fp, $img);
        fclose($fp);
    }

}