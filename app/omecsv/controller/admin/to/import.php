<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class omecsv_ctl_admin_to_import extends desktop_controller{

    function treat(){
        $_GET['ctler'];
        $_GET['add'];
        $finder = kernel::single('omecsv_to_import');
        $finder->main();
    }

}
