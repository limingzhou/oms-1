<?php
$db['appropriation']=array (
  'columns' => 
  array (
    'appropriation_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'editable' => false,
    ),
    'create_time' =>
    array (
        'type' => 'time',
        'label' => '生成日期',
        'width' => 160,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
    ),
    'operator_name' =>
    array (
        'type' => 'varchar(50)',
        'label' => '经办人',
        'width' => 110,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
    ),
    'type' =>
    array (
      'type' =>
      array (
        0 => '调拔单',
        1 => '理货单',
      ),
      'default' => '0',
      'required' => true,
      'label' => '类型',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'memo' =>
    array (
        'type' => 'longtext',
        'editable' => false,
        'comment' => '备注',
    ),
  ),
  'comment' => '库存调整单（调拨单）',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
