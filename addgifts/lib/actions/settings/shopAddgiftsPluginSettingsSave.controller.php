<?php

/**
 *
 * Сохранение настроек плагина
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginSettingsSaveController extends waJsonController
{

    public function execute()
    {

        $plugin = shopAddgiftsPlugin::getInstance();

        //Сохраняем основные настройки
        $settings = waRequest::post('settings', array(), waRequest::TYPE_ARRAY);
        $settings['last_changes'] = time();
        $plugin->saveSettings($settings);

        //Сохраняем шаблоны
        if (method_exists($plugin, 'saveTemplates')) {
            $templates = waRequest::post('templates', array(), waRequest::TYPE_ARRAY);
            $plugin->saveTemplates($templates);
        }

        //Сохраняем настройки витрин
        if (method_exists($plugin, 'saveStorefronts')) {
            $storefronts = waRequest::post('storefronts', array(), waRequest::TYPE_ARRAY);
            $plugin->saveStorefronts($storefronts);
        }
    }


}
