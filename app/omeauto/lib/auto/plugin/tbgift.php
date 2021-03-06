<?php

/**
 * 检查淘宝订单是否有优惠赠品
 *
 * @author danny@shopex.cn
 * @version 0.1
 */
class omeauto_auto_plugin_tbgift extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = false;
    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__PMTGIFT_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {
        $allow = true;
        if($this->_checkStatus($confirmRoles)){
            foreach ($group->getOrders() as  $order) {
                if(($order['abnormal_status'] & ome_preprocess_const::__HASGIFT_CODE) == ome_preprocess_const::__HASGIFT_CODE){
                    $allow = false;
                    $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                    break;
                }
            }

            if(!$allow){
                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
            }
        }
    }

    /**
     * 检查是否启用超卖检查
     */
    private function _checkStatus($configRoles) {
        return true;
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {
        return '淘宝订单有赠品';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {
        return array('color' => 'RED', 'flag' => '赠', 'msg' => '当前淘宝订单有优惠赠品');
    }

}
