<?php

/**
 *
 * Настройки для плагина с поддержкой загрузки файлов и мултивитринностью
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('addgifts');
        $this->view->assign('plugin', $plugin);
    }

}