<?php
class ome_event_data_delivery{

    /**
     *
     * 发货通知单请求数据生成
     * @param array $delivery_id 本地发货通知单(合并后的)id
     */
    public function generate($delivery_id){
        $deliveryObj = app::get('ome')->model('delivery');
        $deliveryOrderObj = app::get('ome')->model('delivery_order');
        $productObj = app::get('ome')->model('products');
        $orderObj = app::get('ome')->model('orders');
        $shopObj = app::get('ome')->model('shop');
        $branchObj = app::get('ome')->model('branch');
        $memberObj = app::get('ome')->model('members');
        $dlyCorpObj = app::get('ome')->model('dly_corp');
        $orderExtendObj = app::get('ome')->model('order_extend');
        $didObj = app::get('ome')->model('delivery_items_detail');
        $data = $deliveryObj->dump($delivery_id,'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));

        //重组发货单明细上的金额信息 单价、平摊优惠价、平摊优惠金额
        $dly_order = $deliveryOrderObj->getlist('*',array('delivery_id'=>$delivery_id),0,-1);

        $pmt_orders = $deliveryObj->getPmt_price($dly_order);
        $sale_orders = $deliveryObj->getsale_price($dly_order);
        $tbdx_flag = false;
        $is_order_invoice = 'false';
        $invoice_items = array();
        if ($data){
            foreach ($data['delivery_items'] as $key => $item){
                foreach ($data['delivery_order'] as $dk => $order){
                    $order_id = $order;
                    break;
                }
                $order_detail = $orderObj->dump($order_id, 'order_source,shop_type');
                if($order_detail['order_source'] == 'tbdx' && $order_detail['shop_type'] == 'taobao'){
                    $tbdx_flag = true;
                    $didArrs = $didObj->dump(array('delivery_id' => $item['delivery_id'], 'delivery_item_id' => $item['item_id']),'item_type,order_obj_id,order_item_id');
             
                    $tbitemObj = app::get('ome')->model('tbfx_order_items');

                    $tbfx_filter = array('obj_id'=>$didArrs['order_obj_id'],'item_id'=>$didArrs['order_item_id']);

                    $ext_item_info = $tbitemObj->getOrderByOrderId($tbfx_filter);
                    $data['delivery_items'][$key]['price'] = round(($ext_item_info[0]['buyer_payment']/$item['number']),2);
                   
                    $data['delivery_items'][$key]['sale_price'] = $ext_item_info[0]['buyer_payment'];                        
                }else{
                    $data['delivery_items'][$key]['price'] = $sale_orders[$item['bn']];
                    
                    $data['delivery_items'][$key]['sale_price'] = $sale_orders[$item['bn']]*$item['number'];
                    
                }
                $data['delivery_items'][$key]['pmt_price'] = $pmt_orders[$item['bn']]['pmt_price'];
                unset($data['delivery_items'][$key]['delivery_id']);
                unset($data['delivery_items'][$key]['item_id']);
                //$items[$key]['sale_price'] = ($sale_orders[$items[$key]['bn']]*$item['number'])-$pmt_order[$items[$key]['bn']]['pmt_price'];
            }

            $order_bns = array();
            foreach ($data['delivery_order'] as $dk => $order){
                $row = $orderObj->dump($order['order_id'], '*', array('order_objects'=>array('*',array('order_items'=>array('*')))));
                 //
                if ($tbdx_flag){
                    $tbfxorders = $orderObj->db->selectrow("SELECT SUM(buyer_payment) as total_amount FROM sdb_ome_tbfx_order_items WHERE order_id=".$order['order_id']."");
                    $row['cost_item'] = $row['total_amount'] = $tbfxorders['total_amount'];
                    
                }
                
                $orderextend = $orderExtendObj->dump(array('order_id'=>$order['order_id']),'receivable');
                $order_bns[] = $row['order_bn'];
                $data['is_cod'] = $row['shipping']['is_cod'];
                $data['total_amount'] += $row['total_amount'];
                $data['discount_fee'] += ($row['discount']+$row['pmt_order']);
                $data['total_goods_amount'] += $row['cost_item'];
                $data['goods_discount_fee'] += $row['pmt_goods'];
                $data['shop_type'] = $row['shop_type'];
                $data['relate_order_bn'] = $row['relate_order_bn'];
                //$data['shop_code'] = $row['shop_bn'];
            
                $shopInfo = $shopObj->dump($row['shop_id'],'shop_bn,name');
                $data['shop_code'] = isset($shopInfo['shop_bn']) ? $shopInfo['shop_bn'] : '';
                $data['shop_name'] = isset($shopInfo['name']) ? $shopInfo['name'] : '';
                
                $dlyCorpInfo = $dlyCorpObj->dump($data['logi_id'],'type');
                $data['logi_code'] = isset($dlyCorpInfo['type']) ? $dlyCorpInfo['type'] : '';
                //
                $data['logistics_costs'] += $row['shipping']['cost_shipping'];
                $receivable =$orderextend['receivable']>0 ? $orderextend['receivable'] :$row['total_amount'];  
                $data['cod_fee'] += ($row['shipping']['is_cod'] == 'true' ? $receivable : 0.00);
                if ($row['is_tax'] == 'true'){
                    $data['is_order_invoice'] = 'true';
                    $data['invoice']['invoice_desc'] =$row['tax_title'];
                    $data['invoice_money'] += ($row['shipping']['is_cod'] == 'true' ? $receivable : $row['total_amount']);
                }
                $branchInfo = $branchObj->db->selectrow('SELECT branch_bn,storage_code FROM sdb_ome_branch WHERE branch_id='.$data['branch_id']);
                $data['storage_code'] = $branchInfo['storage_code'];
                $data['branch_bn'] = $branchInfo['branch_bn'];

                $member_uname = $memberObj->dump(array('member_id'=>$row['member_id']),'uname');
                if($member_uname){
                    $data['member_name'] = $member_uname['account']['uname'];
                }

                if($row['mark_text']){
                    $tmp_memo = array_pop(unserialize($row['mark_text']));
                }
                $data['memo'] = isset($tmp_memo['op_content']) ? $tmp_memo['op_content'] : '';
                $custom_mark='';
                if ($row['custom_mark']){
                    $custom_mark.= kernel::single('ome_func')->format_memo($row['custom_mark']);
                }
                $data['custom_mark'] = $custom_mark;
                foreach ($row['order_objects'] as $ok => $obj){
                    $data['order_objects'][$ok] = array(
                        'order_bn' => $row['order_bn'],
                        'obj_id' => $obj['obj_id'],
                        'obj_type' => $obj['obj_type'],
                        'goods_id' => $obj['goods_id'],
                        'bn' => $obj['bn'],
                        'name' => $obj['name'],
                        'quantity' => $obj['quantity'],
                        'price' => $obj['price'],
                        'pmt_price' => $obj['pmt_price'],
                        'sale_price' => $obj['sale_price'],
                    );

                    foreach ($obj['order_items'] as $ik => $item){
   
                        $data['order_objects'][$ok]['order_items'][$ik] = array(
                            'obj_id' => $obj['obj_id'],
                            'item_id' => $item['item_id'],
                            'item_type' => $item['item_type'],
                            'product_id' => $item['product_id'],
                            'bn' => $item['bn'],
                            'name' => $item['name'],
                            'nums' => $item['quantity'],
                            'price' => $item['price'],
                            'pmt_price' => $item['pmt_price'],
                            'sale_price' => $item['sale_price'],
                        );
                        
                        if ($row['is_tax'] == 'true' && $item['sale_price']>0){
                            $invoice_items[] = array(
                            'item_name'=> $item['name'],
                             'spec'=> $item['spec'],
                             'nums'=>$item['quantity'],
                             'price'=>$item['sale_price'],
                         
                            );
                        }
                         
                    }
                }

                unset($row);
            }

            $data['order_bn'] = implode('|', $order_bns);
        }
        if ($invoice_items){
            $data['invoice']['invoice_items'] = $invoice_items;
        }
        if ($data['pause'] == 'true'){
            $data['pause'] = 'true';
        }

        $data['outer_delivery_bn'] = $data['delivery_bn'];
        unset($data['delivery_bn']);
        unset($data['delivery_id']);
        unset($data['delivery_order']);

        return $data;
    }


}