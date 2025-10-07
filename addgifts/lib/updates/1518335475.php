<?php

//Обновление настроек для цены товара
$settings = $this->getSettings();

foreach ($settings['routes'] as $key => $route_setting) {
    $settings['routes'][$key]['price_comment'] = _wp('под подарком подразумевается стоимость в') . ' {$gift_price}';
    $settings['routes'][$key]['price_comment_cart'] = '(' . _wp('под подарком подразумевается стоимость в') . ' {$gift_price}' . ')';
    $settings['routes'][$key]['price_comment_separate'] = _wp('под подарком подразумевается стоимость в') . ' {$gift_price}';
}

$this->saveSettings($settings);
