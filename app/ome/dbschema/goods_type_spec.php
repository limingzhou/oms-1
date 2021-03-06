<?php
$db['goods_type_spec']=array (
  'columns' => 
  array (
    'spec_id' => 
    array (
      'type' => 'table:specification',
      'pkey' => true,
      'default' => 0,
      'editable' => false,
    ),
    'type_id' => 
    array (
      'type' => 'table:goods_type',
      'default' => 0,
      'pkey' => true,
      'editable' => false,
    ),
    'spec_style' => 
    array (
      'type' => 
      array (
        'select' => '下拉',
        'flat' => '平面',
        'disabled' => '禁用',
      ),
      'default' => 'flat',
      'required' => true,
      'editable' => false,
      'comment' => '渐进式搜索时的样式',
    ),
  ), 
  'comment' => '类型 规格索引表',
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
);