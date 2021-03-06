<?php
$db['spec_values']=array (
  'columns' => 
  array (
    'spec_value_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'spec_id' => 
    array (
      'type' => 'table:specification@ome',
      'default' => 0,
      'required' => true,
      'editable' => false,
      'comment' => '规格id',
    ),
    'spec_value' => 
    array (
      'type' => 'varchar(100)',
      'default' => '',
      'required' => true,
      'editable' => false,
      'is_title' => true,
      'comment' => '规格值',
    ),
    'alias' => 
    array (
      'type' => 'varchar(255)',
      'default' => '',
      'label' => '规格别名',
      'width' => 180,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'spec_image' => 
    array (
      'type' => 'varchar(11)',
      'default' => '',
      'required' => true,
      'editable' => false,
      'comment' => '规格值图片',
    ),
    'p_order' => 
    array (
      'type' => 'number',
      'default' => 50,
      'required' => true,
      'editable' => false,
      'comment' => '排序',
    ),
  ), 
  'comment' => '商店中商品规格值',
  'engine' => 'innodb',
  'version' => '$Rev: 42046 $',
);