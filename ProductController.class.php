<?php
namespace Admin\Controller;
use Think\Controller;
class ProductController extends SuperController {
    // 商品列表
    public function index(){
    	$data=M('product')
    	    ->alias('t')
    	    ->field('t.*,c.c_title,s.s_title')
            ->join('left join tp_classify as c on t.cid=c.id')
            ->join('left join tp_source as s on t.sid=s.id')
    	    ->order('t_addtime desc')
    	    ->page(I('get.p'),10)
    	    ->select();
    	$show = home_page1(M('product'),10,5);
        $this->assign('page',$show);
    	$this->assign('data',$data);
        $this->display();
    }
    // 添加商品
    public function add(){
        if($_POST){
            // 添加参数
            $data=I('post.','','trim');
            $data['t_addtime']=date('Y-m-d H:i:s',time());
   
            // 文件上传
        	if($_FILES){
        		$upload = new \Think\Upload();// 实例化上传类
    		    $upload->maxSize   =     3145728 ;// 设置附件上传大小
    		    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
    		    $upload->rootPath  =     './Public/Uploads/'; // 设置附件上传根目录
    		    $upload->savePath  =     ''; // 设置附件上传（子）目录
    		    // 上传文件 
    		    $info   =   $upload->upload();
    		    if(!$info) {// 上传错误提示错误信息
    		        // $this->error($upload->getError());
                    tips('请上传图片');
    		    }else{// 上传成功
    		       $data['t_img']=C('m').'/Public/Uploads/'.$info['t_img']['savepath'].$info['t_img']['savename'];
    		    }
        	}
            $res=M('product')->add($data);
            if($res!=false){
                tips('添加成功',U('Product/index'));
            }else{
                tips('添加失败');
            }
        }
        
        $classify=M('classify')->select();
        $source=M('source')->select();

        $this->assign('classify',$classify);
        $this->assign('source',$source);
    	$this->display();
    }
    // 修改商品
    public function update(){
    	if($_POST){
            // 修改参数
            $data=I('post.','','trim');

            if(!isset($data['t_status']))
            {
                $data['t_status']=0;
            }

            if(!isset($data['expiry_time']))
            {
                 $data['expiry_time']=0;
            }

          

            // 文件上传
    		if($_FILES){
    			$upload = new \Think\Upload();// 实例化上传类
			    $upload->maxSize   =     3145728 ;// 设置附件上传大小
			    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			    $upload->rootPath  =     './Public/Uploads/'; // 设置附件上传根目录
			    $upload->savePath  =     ''; // 设置附件上传（子）目录
			    // 上传文件 
			    $info   =   $upload->upload();
			    if(!$info) {// 上传错误提示错误信息
			        // $this->error($upload->getError());
                    $data['t_img']==M('product')->where(array('id'=>I('id')))->getField('t_img');
			    }else{// 上传成功
			       $data['t_img']=C('m').'/Public/Uploads/'.$info['t_img']['savepath'].$info['t_img']['savename'];
			    }
    		}

                  

		    $res=M('product')->save($data);
		    if($res!=false){
		    	tips('修改成功',U('Product/index'));
		    }else{
                tips('添加失败');
            }
    	}

    	$id=I('get.id');
    	$data=M('product')->where('id='.$id)->find();
        

        
        $classify=M('classify')->select();
        $source=M('source')->select();
        $config=M('config')->where(array('id'=>1))->find();

         $endtime=strtotime($data['endtime']);
         $time=date('Y-m-d H:i:s',time());
         $times=strtotime($time);
            if($times>$endtime)
            {
                $wh=array(
                    'id'=>$id,
                    'expiry_time'=>1,
                );   
                $order=M("product")->save($wh);              
           }     


        $this->assign('source',$source);
        $this->assign('classify',$classify);
    	$this->assign('data',$data);
        $this->assign('config',$config);
    	$this->display();
    }
    // 删除商品
    public function delete(){
    	$id=I('get.id');
    	// $img=M('classify')->where('id='.$id)->getField('img');
    	$res=M('product')->where('id='.$id)->delete();
    	if($res!=false){
    		// unlink("./public/Uploads/".$img);
    		tips('删除成功',U('Product/index'));
    	}else{
    		tips('删除失败！');
    	}
    }
	//前端显示隐藏
    public function webtoggle(){
    	$pro_type2=I('pro_type2');
		$id=I('id');
    	$res=M('product')->where('id='.$id)->save(array('pro_type2'=>$pro_type2));
    	if($res!=false){
    		if($pro_type2==1){
				$arr['reCode']=20000;
				$arr['type']=2;
			}else{
				$arr['reCode']=20000;
				$arr['type']=1;
			}
    		echo json_encode($arr);die;
    	}else{
			$arr['reCode']=40000;
			$arr['type']=3;
    		echo json_encode($arr);die;
    	}
    }
	//上下架
    public function toggle(){
    	$pro_type=I('pro_type');
		$id=I('id');
    	$res=M('product')->where('id='.$id)->save(array('pro_type'=>$pro_type));
    	if($res!=false){
			if($pro_type==1){
				$arr['reCode']=20000;
				$arr['type']=2;
			}else{
				$arr['reCode']=20000;
				$arr['type']=1;
			}
    		echo json_encode($arr);die;
    	}else{
			$arr['reCode']=40000;
			$arr['type']=3;
    		echo json_encode($arr);die;
    	}
    }
}