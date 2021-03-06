<?php
class allocate extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testAllocate(){
        $data = array (
          'io_type' => 'ALLCOATE',
          'io_bn' => 'R20130530000002',
            'io_status'=>'FINISH',
          'branch_bn' => 'branch1',
          'storage_code' => 'a',
          'create_time' => '1368682745',
         'memo' => '备注啦。。啦啦啦。。',
          'items' => 
          array (
            0 => 
            array (
              'num' => '3',
              'bn' => 'test001',
              'name' => 'test001',
              'price' => '0.000',
            ),
          ),
        );
        $pObj = app::get('ome')->model('products');
        $branch_productObj = app::get('ome')->model('branch_product');
        $pStockObj = kernel::single('console_stock_products');
        $branch = $pObj->db->selectrow('SELECT branch_id FROM sdb_ome_branch where branch_bn=\''.$data['branch_bn'].'\'');
        $branch_id = $branch['branch_id'];
        echo "入库前商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$branch_id);
            echo $item['bn']."可用库存:".$usable_store."\r\n";
        }
        $wms_id = kernel::single('ome_branch')->getWmsIdById(1);
        
        $res = kernel::single('middleware_wms_response', $wms_id)->stockout_result($data);
        echo "出库后商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$branch_id);
            echo $item['bn']."可用库存:".$usable_store."\r\n";
        }
        echo "------------------------\r\n";
        print_r($res);
        #查看此单据是否有异常，如果有异常显示入库数量
        $iso_item = kernel::single('wms_iostockdata')->getIsoBybn($data['io_bn']);
        if ($iso_item){
            echo "以下数据异常\r\n";
            foreach($iso_item as $item){
                echo '货号:'.$item['bn'].'原数量:'.$item['nums'].',实际入库数量:'.$item['normal_num']."\r\n";
            }
        }
    }
}
