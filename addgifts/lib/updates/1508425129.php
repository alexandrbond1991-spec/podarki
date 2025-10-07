<?php

$settings = $this->getSettings();

//При обнволение чтобы не менять текущею логику
$settings['show_revert'] = 0;

//Новые шаблоны
foreach ($settings['routes'] as $key => $route_setting) {
    $settings['routes'][$key]['template_gift'] = file_get_contents($this->path . '/templates/actions/frontend/template_gift.html');
}

$this->saveSettings($settings);
