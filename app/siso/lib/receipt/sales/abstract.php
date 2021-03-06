<?php
class siso_receipt_sales_abstract {

    /**
     * 销售单生成方法
     */
    function create($params,&$msg=array()){
        
        #[拆单]配置是否启动拆单 ExBOY
        $orderSplitLib    = kernel::single('ome_order_split');
        $split_seting     = $orderSplitLib->get_delivery_seting();
        
        $itemsObj = app::get('ome')->model('sales_items');

        $this->_io_data = $params['iostock'];
        unset($params['iostock']);
        $this->_sales_data['sales'] = $this->get_sales_data($params);
 
        //格式化出入库信息内容
        $this->_sales_data = $this->convertSdf($this->_sales_data, $split_seting);//是否启动拆单 ExBOY
        
        if(is_array($this->_sales_data) && count($this->_sales_data)>0){
           foreach($this->_sales_data['sales'] as $k => $sale){
               
               /*------------------------------------------------------ */
               //-- [拆单]过滤部分发货时,不生成销售单  ExBOY
               /*------------------------------------------------------ */
               if(!empty($split_seting))
               {
                   $get_order_id       = intval($sale['order_id']);
                   $get_delivery_id    = intval($sale['delivery_id']);
                   
                   $allow_commit    = $orderSplitLib->check_order_all_delivery($get_order_id, $get_delivery_id, true);
                   if($allow_commit)
                   {
                       continue;//部分拆分OR部分发货时,跳过生成销售单 ExBOY
                   }
               }
               
                if(!$this->check_required($sale,$msg)){
                    return false;
                }
                $this->divide_data($sale,$main,$item);

                if($this->_mainvalue($main,$msg) && $this->_itemvalue($item,$msg)){
                    $sale_id = $this->gen_id();//获取销售编号
                    $main['sale_id'] = $sale_id;
                    foreach($item as $item_key=>$value){
                        $value['branch_id'] = $sale['branch_id'];
                        $value['sale_id'] = $sale_id;
                        $item[$item_key] = $value;
                    }

                    $item_sql = ome_func::get_insert_sql($itemsObj,$item);
                    if ( kernel::database()->exec($item_sql) ){
                        $this->_add_Sales($main);
                    }
                }else{
                    return false;
                }
            }
             //更新销售单上的成本单价和成本金额等字段

                kernel::single('tgstockcost_instance_router')->set_sales_iostock_cost(0,$this->_io_data);
            return true;
        }else{
            return false;
        }
    }


    private function convertSdf($data, $split_seting=array()){
        /*------------------------------------------------------ */
        //-- [拆单]获取多个发货单对应所有iostock出库单 ExBOY
        /*------------------------------------------------------ */
        $orderSplitLib    = kernel::single('ome_order_split');
        if(!empty($split_seting))
        {
            $order_delivery_iostock_data    = $orderSplitLib->get_delivery_iostock_data($this->_io_data, true);
        }
        
        foreach ($data['sales'] as $k => $sale){
            
            /*------------------------------------------------------ */
            //-- [拆单]多个发货单累加物流成本 ExBOY
            /*------------------------------------------------------ */
            if(!empty($split_seting))
            {
                $get_order_id       = intval($sale['order_id']);
                $get_delivery_id    = intval($sale['delivery_id']);
                
                $delivery_cost_actual   = $orderSplitLib->count_delivery_cost_actual($get_order_id, true);
                if($delivery_cost_actual)
                {
                    $data['sales'][$k]['delivery_cost_actual']  = $delivery_cost_actual;
                }
            }
            
            if ($data['sales'][$k]['sales_items']){
                foreach ($data['sales'][$k]['sales_items'] as $kk=>$vv){
                    
                    #[拆单]多个发货单时_iostock_id为NULL重新获取 ExBOY
                    if(!empty($this->_io_data[$vv['item_detail_id']]['iostock_id']))
                    {
                        $vv['iostock_id'] = $this->_io_data[$vv['item_detail_id']]['iostock_id'];
                    }
                    else 
                    {
                        $vv['iostock_id'] = $order_delivery_iostock_data[$vv['item_detail_id']]['iostock_id'];
                    }
                    
                    $data['sales'][$k]['sales_items'][$kk] = $vv;

                    #[拆单]多个发货单时_iostock_bn为NULL重新获取 ExBOY
                    if(!empty($this->_io_data[$vv['item_detail_id']]['iostock_bn']))
                    {
                        $iostock_bn = $this->_io_data[$vv['item_detail_id']]['iostock_bn'];
                    }
                    else 
                    {
                        $iostock_bn = $order_delivery_iostock_data[$vv['item_detail_id']]['iostock_bn'];
                    }
                }
            }
            $data['sales'][$k]['iostock_bn'] = $iostock_bn;
            $sale_bn = $this->get_salse_bn();
            $data['sales'][$k]['sale_bn'] = $sale_bn;
        }
        return $data;
    }

    /**
     * 添加销售主表记录
     */
    function _add_Sales($data){
        $salesObj = app::get('ome')->model('sales');
        return $salesObj->save($data);
    }

    /**
     * 生成销售单的主键id号
     */
    function gen_id(){
        list($msec, $sec) = explode(" ",microtime());

        $id = $sec.strval($msec*1000000);
          $conObj = app::get('ome')->model('concurrent');
        if($conObj->is_pass($id,'sales')){
            return $id;
        } else {
            return $this->gen_id();
        }
    }

    /**
     * 检查所有必填字段
     */
    function check_required($data,&$msg){
        $msg = array();
        $arrMain = array('iostock_bn','sale_amount','delivery_cost','operator','branch_id'); //主表必须字段
        $arrItems = array('bn','nums'); //明细表必须字段
        if(is_array($data) && count($data) > 0){
            $arrExist = array_keys($data);

            if(count(array_diff($arrMain,$arrExist))){
                $msg[] = '主表中必填字段不全';
                return false;
            }
            foreach($arrMain as $key){
                $tmp_value = trim($data[$key]);
                if(is_null($tmp_value) || $tmp_value === ''){
                    $msg[]=$key.'主表中必填字段值不能为空';
                }
            }
            if(in_array('sales_items',$arrExist) && is_array($data['sales_items'])){

                foreach($data['sales_items'] as $keys=>$contents){
                    $arrI = array_keys($contents);
                    if(count(array_diff($arrItems,$arrI))){
                        $msg[] ='明细表-' . $keys .'-' . '必填字段不全';
                    }else{
                        foreach($contents as $key=>$value){
                            if(in_array($key,$arrItems)){
                               empty($value) ? $msg[] = '明细表' . $keys . '-' .$key . '必填字段值不能为空' : '';
                            }
                        }
                    }
                }

                if(count($msg)){
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 拆分出主表与子表数据
     */
    function divide_data($data,&$mainArr,&$itemArr){
        if($data){
            foreach($data as $key=>$value){
                if($key == 'sales_items'){
                    $itemArr = $data[$key];
                }else{
                    $mainArr[$key] = $data[$key];
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 检查明细表值是否符合
     */
    function _itemvalue($data,&$msg){
        $rea = '字段类型不符(子表)';
        if(is_array($data)){
            foreach($data as $key=>$val){
                foreach($val as $field=>$content){
                    if($content != ''){
                        switch ($field){
                                //bigint(20) unsigned
                            case 'sale_id':
                            case 'iostock_id':
                                if(is_numeric($content) && strlen($content)<=20 && $content>0){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                //int(10) unsigned
                            case 'item_id':
                                if(is_numeric($content) && strlen($content)<=10 && $content>0){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                //varchar(32)
                            case 'bn':
                            case 'sell_code':
                                if(is_string($content) && strlen($content)<=32){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                // mediumint(8) unsigned
                            case 'nums':
                            case 'branch_id':
                                if(is_numeric($content) && strlen($content)<=8 && $content>0){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                                //decimal(20,3)
                            case 'price':
                            case 'cost':
                            case 'cost_tax':
                                if(is_numeric($content) && strlen($content)<=20){
                                    continue;
                                } else{
                                    $msg[] = $key .'-'. $field.'-'.$rea;
                                }
                                break;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 检查主表字段值是否符合
     */
    function _mainvalue($data,&$msg){
        $rea = '字段类型不符(主表)';
        foreach($data as $key=>$content){
            if($content != ''){
                switch ($key){
                        //bigint(20) unsigned
                    case 'sale_id':
                        if(is_numeric($content) && strlen($content)<=20 && $content>0){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //varchar(32)
                    case 'sale_bn':
                    case 'iostock_bn':
                    case 'shop_id':
                        if(is_string($content) && strlen($content)<=32){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //int(10) unsigned
                    case 'sale_time':
                    case 'member_id':
                        if (!empty($content)){
                            if(is_numeric($content) && strlen($content)<=10 && $content>0){
                                continue;
                            } else{
                                $msg[] = $key .'-'.$rea;
                            }
                        }
                        break;
                        //decimal(20,3)
                    case 'sale_amount':
                    case 'cost':
                    case 'delivery_cost':
                    case 'additional_costs':
                    case 'deposit':
                    case 'discount':
                        if(is_numeric($content) && strlen($content)<=20){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //varchar(30)
                    case 'operator':
                        if(is_string($content) && strlen($content)<=30){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //mediumint(8) unsigned
                    case 'branch_id':
                        if(is_numeric($content) && strlen($content)<=8 && $content>0){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                        //enum('0','1')
                    case 'pay_status':
                        if(is_numeric($content) && strlen($content)<=2){
                            continue;
                        } else{
                            $msg[] = $key .'-'.$rea;
                        }
                        break;
                }
            }
        }
        return true;
    }

    /**
     * 生成销售单单号
     */
    function get_salse_bn($num = 0){
        $type = 'SALSE';
        $prefix = 'S'.date('Ymd');
        $sign = kernel::single('eccommon_guid')->incId($type, $prefix, 6, true);
        return $sign;
        
        /*
        $type = 'SALSE';

        if($num >= 1){
            $num++;
        }else{
            $sql = "SELECT id FROM sdb_ome_concurrent WHERE `type`='$type' and `current_time`>'".strtotime(date('Y-m-d'))."' and `current_time`<=".time()." order by id desc limit 0,1";
            $arr = kernel::database()->select($sql);
            if($id = $arr[0]['id']){
                $num = substr($id,-6);
                $num = intval($num)+1;
            }else{
                $num = 1;
            }
        }

        $po_num = str_pad($num,6,'0',STR_PAD_LEFT);
        $salse_bn = 'S'.date(Ymd).$po_num;

        $conObj = app::get('ome')->model('concurrent');
        if($conObj->is_pass($salse_bn,$type)){
            return $salse_bn;
        } else {
            if($num > 999999){
                return false;
            }else{
                return $this->get_salse_bn($num);
            }
        }
        */
    }
    
}