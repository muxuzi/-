<?php
namespace Home\Controller;
use Think\Controller;
class InforController extends BaseController {
    // 权益资讯
    public function rights(){
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
        $map['class_name']=2;
		$re=M("infor")->where($map)->select();
		$this->assign("re",$re);
       $this->display();
    }
    

}