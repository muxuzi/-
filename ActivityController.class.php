<?php
namespace Home\Controller;
use Think\Controller;
class ActivityController extends BaseController {
    // 全部活动
	public function index(){
		$re=M("activity")->order("id desc")->select();
		foreach($re as $k=>$v){
			$re[$k]["starttime"]=str_replace("-","/",$v["starttime"]);
			$re[$k]["endtime"]=str_replace("-","/",$v["endtime"]);
		}
		$collect=M("collect_acti")->field("aid")->where("uid=".$_SESSION["user_id"])->select();
		$hidden=M("hidden_acti")->field("aid")->where("uid=".$_SESSION["user_id"])->select();
		
		foreach($collect As $k2=>$v2){
			$colarr[]=$v2["aid"];
		}
		foreach($hidden As $k3=>$v3){
			foreach($re As $k4=>$v4){
				if($v4["id"]==$v3["aid"]){
					unset($re[$k4]);
				}
			}
			
		}

		$collect=implode(",",$colarr);
		$collect_uid=implode(",",$collect_uid);
		$this->assign("collect",$collect);
		$this->assign("uid",session('user_id'));
		$this->assign("re",$re);
        $this->display();
    }
    // 活动收藏
    public function mycollection(){
		$collect=M("collect_acti")->where("uid=".$_SESSION["user_id"])->getField('aid',true);
		$aid=implode(",",$collect);
		$wh['id']=array('in',$aid);
		$re=M("activity")->order("id desc")->where($wh)->select();
		foreach($re as $k=>$v){
			$re[$k]["starttime"]=str_replace("-","/",$v["starttime"]);
			$re[$k]["endtime"]=str_replace("-","/",$v["endtime"]);
		}

		$this->assign("re",$re);
        $this->display();
    }
	
    public function detail(){
		$id=I('id');
        $re=M("activity")->where("id=".$id)->find();
		$this->assign("re",$re);
        $this->display();
    }
    
	public function ajaxdel(){
		$id=I('post.id');
		$re=M("hidden_acti")->add(array("uid"=>$_SESSION["user_id"],"aid"=>$id));
		if($re){
			$this->ajaxreturn(1);exit;
		}else{
			$this->ajaxreturn(0);exit;
		}
	}

	public function ajaxAddCollect(){
		$id=I('post.id');
		$check=M("collect_acti")->where("uid=".$_SESSION["user_id"]." and aid=".$id."")->find();
		
		if($check){
			M("collect_acti")->where("uid=".$_SESSION["user_id"]." and aid=".$id)->delete();
			M("activity")->where("id=".$id)->setDec('collect');
			$this->ajaxreturn(2);exit;
		}else{
			$re=M("activity")->where("id=".$id)->setInc('collect');
			if($re){
			M("collect_acti")->add(array("uid"=>$_SESSION["user_id"],"aid"=>$id));
				$this->ajaxreturn(1);exit;
			}else{
				$this->ajaxreturn(0);exit;
			}
		}
		
		
	}
}