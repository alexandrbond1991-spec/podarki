<?php

//Обнволение 2.0 (копирование loading32.gif)
try {

    //Копируем изображение
    $css_dir = wa()->getDataPath('plugins/' . $this->id . '/img/', true, 'shop') . 'loading32.gif';
    $img = wa()->getAppPath('plugins/' . $this->id . '/img/', 'shop') . 'loading32.gif';

    if (!file_exists($css_dir)) {
        waFiles::copy($img, $css_dir);
    }

} catch (Exception $e) {
}
