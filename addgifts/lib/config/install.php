<?php
/**
 * Установка переменных по умолчанию
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

$this->createDefaultStorefrontVariables();

try {

    //Копируем изображение
    $css_dir = wa()->getDataPath('plugins/' . $this->id . '/img/', true, 'shop') . 'loading32.gif';
    $img = wa()->getAppPath('plugins/' . $this->id . '/img/', 'shop') . 'loading32.gif';

    waFiles::copy($img, $css_dir);

} catch (Exception $e) {
}
