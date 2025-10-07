<?php
/**
 *
 * Сортировка правил
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */


class shopAddgiftsPluginBackendSortRuleController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $direction = waRequest::post('direction', 1, waRequest::TYPE_INT);

        $model = new shopAddgiftsRulesModel();
        $model->resortRules($id, $direction);

    }
}