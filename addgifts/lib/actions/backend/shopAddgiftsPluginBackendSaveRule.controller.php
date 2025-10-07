<?php
/**
 *
 * Сохранение правила для подарка
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */


class shopAddgiftsPluginBackendSaveRuleController extends waJsonController
{
    public function execute()
    {
        $rule = waRequest::post('rule', array());
        $model = new shopAddgiftsRulesModel();
        $model->save($rule);
    }
}