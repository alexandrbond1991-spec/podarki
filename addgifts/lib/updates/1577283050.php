<?php

//Обнволение 2.3 - подарок по сумме в корзине
$model = new waModel();

try {
    $sql = "SELECT is_cart_sum FROM `shop_addgifts__rules` WHERE 0";
    $model->query($sql);
} catch (Exception $e) {
    $sql = "ALTER TABLE `shop_addgifts__rules` ADD COLUMN is_cart_sum int NULL DEFAULT 0";
    $model->exec($sql);
}
