<?php
namespace Home\Controller;
use Think\Controller;
class BaseController extends Controller {
    public function _initialize(){
        // session('user_id','221');
        $uid=session('user_id');
        $uiddata=M('personal')->where(array('id'=>$uid))->find();
        if(!$uiddata){
        // if(!$uid){
            //获取当前网页，授权后跳回
            $path =  $_SERVER['REQUEST_URI'];
            //跳转到微信授权
            header("Location:".U('Auth/index')."?path=".$path);
        }
    }

}