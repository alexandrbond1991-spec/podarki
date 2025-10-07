<?php

/**
 *
 * Поиск товаров
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginBackendSearchProductController extends waJsonController
{
    public function execute()
    {
        $q = waRequest::get('q', '', waRequest::TYPE_STRING_TRIM);
        $ids = waRequest::get('ids', array(), waRequest::TYPE_ARRAY_INT);

        $plugin = wa('shop')->getPlugin('addgifts');

        if (empty($ids)) {
            $this->response['products'] = $plugin->searchProducts($q);
        }else{
            $this->response['products'] = $plugin->getProducts($ids);
        }
    }
}