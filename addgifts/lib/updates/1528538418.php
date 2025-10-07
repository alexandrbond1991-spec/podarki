<?php

// Обновление таблицы, индексы не критичные операции, поэтому разделены от по настоящему критичной,
// чтобы она выполнилась даже если будут ошибки в других запросах

$model = new waModel();

try {
    $model->query('ALTER TABLE `shop_addgifts` DROP INDEX `product_gift_id_index`');
    $model->query('ALTER TABLE `shop_addgifts` DROP INDEX `product_id_uindex`');
    $model->query('ALTER TABLE `shop_addgifts` DROP INDEX `category_id_uindex`');
} catch (Exception $e) {
}

try {

    $model->query('ALTER TABLE `shop_addgifts` CHANGE `product_gift_id` `gift_id` INT NOT NULL');

    $model->query('ALTER TABLE `shop_addgifts` ADD INDEX `product_id_index` (`product_id`)');
    $model->query('ALTER TABLE `shop_addgifts` ADD INDEX `category_id_index` (`category_id`)');
    $model->query('ALTER TABLE `shop_addgifts` ADD INDEX `gift_id_index` (`gift_id`)');
} catch (Exception $e) {
}
