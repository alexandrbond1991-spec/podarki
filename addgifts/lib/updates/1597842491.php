<?php

$settings = $this->getSettings();

//При обнволение чтобы не менять текущею логику
$settings['cart_limit'] = '';

$this->saveSettings($settings);