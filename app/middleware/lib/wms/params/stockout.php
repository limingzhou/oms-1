<?php
/**
* 出库单回传参数校验
* @author dqiujing@gmail.com 独孤羽
* @copyright shopex.cn 2013.4.27
*/
class middleware_wms_params_stockout{

    /**
    * 出库单结果回传参数校验
    * @access public
    * @param Array $params 接口参数
    * @param String $msg 错误消息
    * @param String $msg_code 错误消息
    * @return bool
    */
    public function result($params,&$msg='',&$msg_code=''){

        #必填字段
        $required = array('io_type','io_bn','branch_bn','io_status');
        foreach ($required as $field){
            if(!isset($params[$field]) || empty($params[$field])){
                $msg = '参数:'.$field.'不能为空';
                return false;
            }
        }

        return true;
    }

}