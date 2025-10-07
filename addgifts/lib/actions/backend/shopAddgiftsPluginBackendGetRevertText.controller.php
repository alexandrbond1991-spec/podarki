<?php

/**
 *
 * Текст автоматической обратной связи
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://echo-company.ru
 */
class shopAddgiftsPluginBackendGetRevertTextController extends waJsonController
{
    public function execute()
    {
        $rule = waRequest::post('rule', array(), waRequest::TYPE_ARRAY);
        $instance = shopAddgiftsPlugin::getInstance();

        $revert = $instance->getRevertTextFromRule($rule);

        $this->response = array(
            'revert' => html_entity_decode(strip_tags($revert))
        );
    }
}