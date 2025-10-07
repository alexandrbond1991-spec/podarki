<?php
//Обнволение 1.6

//Обновление настроек для наклеек
$settings = $this->getSettings();

foreach ($settings['routes'] as $key => $route_setting) {
    $settings['routes'][$key]['badge_text'] = _wp('+Подарок');
    $settings['routes'][$key]['badge_text_many'] = _wp('+Подарки');
    $settings['routes'][$key]['many_badges'] = 1;
}

$this->saveSettings($settings);