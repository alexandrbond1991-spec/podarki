<?php
return array(
    'shop_addgifts__rules' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'rule' => array('mediumtext', 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'status' => array('int', 11, 'null' => 0, 'default' => '1'),
        'is_cart_sum' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_addgifts__storefronts' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'storefront' => array('varchar', 255),
        'value' => array('mediumtext'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'storefront' => 'storefront',
        ),
    ),
);
