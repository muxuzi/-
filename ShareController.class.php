<?php
namespace Home\Controller;
use Think\Controller;
class ShareController extends Controller {
    //分享推荐
	public function share(){
		$get_uid=I('uid');
		if(!$get_uid){
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
		}else{
			$scode=M('personal')->where(array('id'=>$get_uid))->find();
			$qrcode_url = 'http://'.$_SERVER['HTTP_HOST'].C('m').$scode['tem_code'];
			$this->assign('qrcode_url',$qrcode_url);
		}
		
		//分享接口配置
        $wxconfig = wx_share_init();

        $this->assign('wxconfig', $wxconfig);
        $this->assign('uid',$uid);
		$this->display();
	}
	
}