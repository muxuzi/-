<?php
namespace Admin\Controller;
use Think\Controller;
class RecomController extends SuperController {
    public function index(){


    	//$where['a.parent_id']=array('neq','0');

    	$recome=M("push")
    		  ->alias('a')
    		  ->field('a.*,b.parent_id as b_parent_id,b.nickname')
    		  ->join('left join tp_personal as b on b.id=a.parent_id')
    		  //->where($where)
    		  ->order("time desc")
    		  ->page(I('get.p'),10)
    		  ->select();

    	
    	foreach($recome as $k=>$v)
    	{    		
    		$recome[$k]['time']=date('Y-m-d H:i:s',$v['time']);
    		 $recome[$k]['nickname']=base64_decode($v['nickname']);
    	}
    	// P($recome);
    	$show = home_page1(M('push'),10,5);
    	 $this->assign('page',$show);
    	$this->assign('recome',$recome);
        $this->display();
    }

	// 导出exl
    public function csv(){
        $fist1=I('fist');
        $last1=I('last');
        var_dump($fist1);
        var_dump($last1);exit;
		$fist=strtotime($fist1);
		$last=strtotime($last1);
        if(empty($fist)&&empty($last)){
             //$wh='1=1';
			 $wh['o.status']=1;
        }elseif(!empty($fist)&&empty($last)){
            $wh['paytime']=array('egt',$fist);
			$wh['o.status']=1;
            
        }elseif(empty($fist)&&!empty($last)){
            $wh['paytime']=array('elt',$last);
			$wh['o.status']=1;
           
        }elseif(!empty($fist)&&!empty($last)){
            $wh['paytime']=array('between',"$fist,$last");
			$wh['o.status']=1;
        }
		
		
		$data=M("order")
				->alias('o')
				->field('o.*,os.status as state,p.username,po.t_title')
				->join('left join tp_order_sell as os on o.o_number=oid')
				->join('left join tp_personal as p on p.id=o.uid')
				->join('left join tp_product as po on po.id=o.pid')
				->where($wh)
                ->select();
		Vendor('emoji.lib.emoji');
        
        // 导出Exl
		Vendor("PHPExcel.PHPExcel");
		Vendor("PHPExcel.PHPExcel.Worksheet.Drawing");
		Vendor("PHPExcel.PHPExcel.Writer.Excel2007");
        $objPHPExcel = new \PHPExcel();
        
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
    
        $objActSheet = $objPHPExcel->getActiveSheet();
        
        // 水平居中（位置很重要，建议在最初始位置）
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('B1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        
		$objActSheet->setCellValue('A1', '用户id');
        $objActSheet->setCellValue('B1', '用户名');
        $objActSheet->setCellValue('C1', '订单号');
        $objActSheet->setCellValue('D1', '购买商品ID名称');
        $objActSheet->setCellValue('E1', '购买商品数量');
        $objActSheet->setCellValue('F1', '价格');
		$objActSheet->setCellValue('G1', '总价');
		$objActSheet->setCellValue('H1', '购买时间');
		$objActSheet->setCellValue('I1', '订单状态');
        // 设置个表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        
        // 垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		
		foreach($data as $k=>$v){
			$k +=2;
			$times=date('Y-m-d H:i:s',$v['paytime']);
			$objActSheet->setCellValue('A'.$k, $v['uid']); 
            $objActSheet->setCellValue('B'.$k, $v['username']); 
			$objActSheet->setCellValue('C'.$k, $v['o_number']);    
			$objActSheet->setCellValue('D'.$k, $v['t_title']);
			$objActSheet->setCellValue('E'.$k, $v['num']);
			$objActSheet->setCellValue('F'.$k, $v['price']);
			$objActSheet->setCellValue('G'.$k, $v['sum']);
			$objActSheet->setCellValue('H'.$k, $times);
			if($v["state"]==1){
				$objActSheet->setCellValue('I'.$k, "待使用");
			}elseif($v["state"]==2){
				$objActSheet->setCellValue('I'.$k, "已完成");
			}elseif($v["state"]==3){
				$objActSheet->setCellValue('I'.$k, "纠纷中");
			}
            
            // 表格高度
            $objActSheet->getRowDimension($k)->setRowHeight(20);
            
        }
		$filename=$fist1.'-'.$last1.'订单表';
        $fileName = iconv("utf-8", "gb2312", $fileName);
        //重命名表
        // $objPHPExcel->getActiveSheet()->setTitle('test');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=订单表.xlsx");
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); //文件通过浏览器下载
        // END    
    } 
	//导出到exel表
	 /* function csv(){
		$data=I();
        $fist1=I('fist');
        $last1=I('last');
		$fist=strtotime($fist1);
		$last=strtotime($last1);
        if(empty($fist)&&empty($last)){
             $wh='1=1';
        }elseif(!empty($fist)&&empty($last)){
            $wh['paytime']=array('egt',$fist);
            
        }elseif(empty($fist)&&!empty($last)){
            $wh['paytime']=array('elt',$last);
           
        }elseif(!empty($fist)&&!empty($last)){
            $wh['paytime']=array('between',"$fist,$last");
        }
		$filename=$fist1.'-'.$last1.'订单表';
		$content=M("order")
				->alias('o')
				->field('o.*,p.username,po.t_title')
				->join('left join tp_personal as p on p.id=o.uid')
				->join('left join tp_product as po on po.id=o.pid')
				->where($wh)
                ->select();
		
		//print_r($content);exit;
    	header("Content-type: application/vnd.ms-excel; charset=utf-8");
	    Header("Content-Disposition: attachment; filename=".$filename.".csv");  
		
		echo "微信名,订单号,购买商品ID名称,购买商品数量,价格,总价,购买时间,订单状态\r\n";
		// echo "微信名+微信号+联系方式（手机号+QQ号）+支付宝号+支付宝姓名+购买商品ID+购买商品ID名称+购买商品数量";
		$str='';
		foreach ($content as $key => $value) {
			if($value["status"]==0){
				$content[$key]['status']="待付款";
			}
			if($value["status"]==1){
				$content[$key]['status']="待使用";
			}
			if($value["status"]==2){
				$content[$key]['status']="已完成";
			}
			if($value["status"]==3){
				$content[$key]['status']="纠纷中";
			}
			$content[$key]['paytime']=date("Y-m-d H:i:s",$value['paytime']);
			$str.=$value['username'].','.$content[$key]['o_number'].','.$content[$key]['t_title'].','.$content[$key]['num'].','.$content[$key]['price'].','.$content[$key]['sum'].','.$content[$key]['paytime'].','.$content[$key]['status']."\r\n";
		}
		echo $str;
    }  */
	
	// 过滤掉emoji表情
}

   