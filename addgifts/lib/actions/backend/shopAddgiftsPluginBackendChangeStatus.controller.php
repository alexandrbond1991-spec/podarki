<?php

/**
 *
 * Установка статуса
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginBackendChangeStatusController extends waJsonController
{

    public function execute()
    {
        $ids = waRequest::post('ids', array(), waRequest::TYPE_ARRAY_INT);
        $status = waRequest::post('status', 1, waRequest::TYPE_INT);


        $model = new shopAddgiftsRulesModel();
        $model->updateStatus($ids, $status);
    }

}