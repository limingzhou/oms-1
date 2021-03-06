<?php
class ome_ctl_admin_goods_cat extends desktop_controller{
    /*
        %1 - id
        %2 - title
        $s - string
        $d - number
    */
    var $workground = 'goods_manager';

    function index(){
        
        $objCat = $this->app->model('goods_cat');
        if($objCat->checkTreeSize()){
            $this->pagedata['hidenplus']=true;
        }
        $tree=$objCat->get_cat_list();
        $this->pagedata['tree_number']=count($tree);
        if($tree){
            foreach($tree as $k=>$v){
                $tree[$k]['link'] = array('cat_id'=>array(
                                'v'=>$v['cat_id'],
                                't'=>app::get('base')->_('商品类别').app::get('base')->_('是').$v['cat_name']
                            ));
            }
        }
        $this->pagedata['tree']= &$tree;
        $depath=array_fill(0,$objCat->get_cat_depth(),'1');
        $this->pagedata['depath']=$depath;
        $this->page('admin/goods/category/map.html');
    }
    
    function addnew($nCatId = 0){
        $this->_info($nCatId);
    }

    function _info($id=0,$type='add'){
        $objCat = $this->app->model('goods_cat');
        $catList =$objCat->getMapTree(0,'');
        $aCatNull[] = array('cat_id'=>0,'cat_name'=>app::get('base')->_('----无----'),'step'=>1);
        if(empty($catList)){
            $catList = $aCatNull;
        }else{
            $catList = array_merge($aCatNull, $catList);
        }
        $this->pagedata['catList'] = $catList;
        $oGtype = $this->app->model('goods_type');
            $this->pagedata['gtypes'] = $oGtype->getList('type_id,name');
            $this->pagedata['gtype']['status'] = $oGtype->checkDefined();
            $aCat = $objCat->dump($id);
            $this->pagedata['cat']['parent_id'] = $aCat['cat_id'];
            $this->pagedata['cat']['type_id'] = $aCat['type_id'];
            if($type == 'edit'){
                $this->pagedata['cat']['cat_id'] = $aCat['cat_id'];
                $this->pagedata['cat']['cat_name'] = $aCat['cat_name'];
                $this->pagedata['cat']['parent_id'] = $aCat['parent_id'];
                $this->pagedata['cat']['p_order'] = $aCat['p_order'];
            }
        $this->page('admin/goods/category/info.html');
    }
    
     function save(){
        $this->begin('index.php?app=ome&ctl=admin_goods_cat&act=index');
        $objCat = $this->app->model('goods_cat');        
        if($objCat->save($_POST['cat']))
            $this->end(true,'保存成功');
        else
            $this->end(false,'保存失败');
    }
    
    function toRemove($nCatId){
        $this->begin('index.php?app=ome&ctl=admin_goods_cat&act=index');
        $objCat = $this->app->model('goods_cat'); 
        $cat_sdf = $objCat->dump($nCatId);
  
        if($objCat->toRemove($nCatId)){
            $this->end(true,$cat_sdf['cat_name'].app::get('base')->_('已删除'));
            
        }
        $this->end(false,app::get('base')->_('删除失败：本分类下还有子分类'));
    }
    
    function edit($nCatId){
        $this->_info($nCatId,'edit');   
    }
    
    function getByStr(){
        header('Content-type: application/json');

        $objCat = $this->app->model('goods_cat');        
       
        $data = $objCat->getCatLikeStr($_POST['kw']);

        echo $data;
    }

}
