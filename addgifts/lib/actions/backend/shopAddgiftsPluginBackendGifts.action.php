<?php

/**
 *
 * Обработка бакенда (actions)
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginBackendGiftsAction extends waViewAction
{

    public function execute()
    {
        $plugin = wa('shop')->getPlugin('addgifts');
        $this->view->assign('plugin', $plugin);
    }

}