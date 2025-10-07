<?php

/**
 *
 * Удаление правила
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginBackendDeleteRuleController extends waJsonActions
{

    public function execute($action)
    {
        $id = waRequest::get('id', 0, waRequest::TYPE_INT);

        if (!empty($id)) {
            $model = new shopAddgiftsRulesModel();
            $model->deleteById($id);
        }
    }

}