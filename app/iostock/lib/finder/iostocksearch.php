<?php

class iostock_finder_iostocksearch{
    var $column_branch_id = '仓库编号';
    function column_branch_id($row){
        $branchObj = app::get('ome')->model('branch');
        $branch = $branchObj->dump(array('branch_id'=>$row['branch_id']),'branch_bn');
      return $branch['branch_bn'];
    }

    var $column_name = '货品名称';
    function column_name($row){
       $proObj = app::get('ome')->model('products');
       $name = $proObj->dump(array('bn'=>$row['bn']),'name');
      return $name['name'];
    }
    
    var $addon_cols = 'supplier_name,nums,original_id';
    var $column_supplier = '供应商';
    var $column_supplier_width = 150;
    function column_supplier($row){
      return $row[$this->col_prefix . 'supplier_name'];
    }
    
    var $column_nums = "出入库数量";
    //var $column_nums_width = "80";
    function column_nums($row){
    	$iostock_instance = kernel::service('ome.iostock');
     	if($iostock_instance->getIoByType($row['type_id'])){
     		return '+'.	$row[$this->col_prefix .'nums'];
     	}else{
     		return '-'.	$row[$this->col_prefix .'nums'];
     	}
    }
    
    private $appropriation_type_ids = array("4","40","11");
    var $column_appropriation_no = '调拨单号';
    var $column_appropriation_no_width = "130";
    function column_appropriation_no($row){
        if(in_array($row["type_id"],$this->appropriation_type_ids)){
            $taoguaniostockorder_iso_obj = app::get('taoguaniostockorder')->model('iso');
            $taoguaniostockorder_info = $taoguaniostockorder_iso_obj->dump(array('iso_id'=>$row[$this->col_prefix .'original_id']),'appropriation_no');
            return $taoguaniostockorder_info["appropriation_no"];
        }else{
            return "-";
        }
    }

}