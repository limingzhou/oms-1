<?php
/**
* ecstore(ECSTORE系统)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: ecstore.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_shopex_publicb2c extends apibusiness_request_shopexabstract
{
    /**
     * 获取必要的发货数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author 
     **/
    protected function format_delivery($delivery)
    {
        $this->_shop['area'] = $delivery['consignee']['area'];

        return parent::format_delivery($delivery);
    }// TODO TEST
}