<?php

/**
 * Основной класс плагина Подарки
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPlugin extends shopAddgiftsEchoPlugin
{

    /**
     * Определение прав доступа
     * @param waRightConfig $config
     */
    public function rightsConfig(waRightConfig $config)
    {
        $config->addItem('shop_' . $this->id . '_header', _wp('Подарки'), 'header');
        $config->addItem('plugin.' . $this->id . '.settings', _wp('Настройка плагина'));
        $config->addItem('plugin.' . $this->id . '.set', _wp('Установка подарков'));
    }
}