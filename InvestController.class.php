<?php
namespace Home\Controller;
use Think\Controller;
class InvestController extends BaseController {
    // 权益资讯
	public function index(){
		if(IS_POST){
			$data=I('post.');
			$data["addtime"]=time();
			$re=M("invest")->add($data);
			if($re){
				echo 1;die;
				// tips("提交成功！", U('index'));
			}else{
				echo 2;die;
				// tips("请稍后重试！");
			}
		}
		if($_GET['type'] !== NULL){
            $type = $_GET['type'];
			
        }else{
			 $type = 1;
		}
		$this->assign("type",$type);
        $this->display();
    }
    public function sellcard(){
        $re=M("infor")->where("class_name=1")->order("id desc")->select();
		$this->assign("re",$re);
        $this->display();
    }
    //常见问题
    public function qa(){
		if($_GET['class_name'] !== NULL){
            $map['class_name2'] = $_GET['class_name'];
        }else{
			 $map['class_name2'] = 3;
		}
		$re=M("infor")->where($map)->select();
		$this->assign("re",$re);
        $this->display();
    }
   

}