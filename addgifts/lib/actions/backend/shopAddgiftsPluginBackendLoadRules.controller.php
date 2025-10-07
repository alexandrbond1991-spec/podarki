<?php
/**
 *
 * Получения списка правил
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopAddgiftsPluginBackendLoadRulesController extends waJsonController
{
    public function execute()
    {
        $model = new shopAddgiftsRulesModel();

        $rules = $model->order('sort asc')->fetchAll();

        $result = array();

        $instance = shopAddgiftsPlugin::getInstance();

        foreach ($rules as $key => $rule) {
            $decoded_rule = shopAddgiftsRulesModel::decodeRule($rule);
            $decoded_rule['check_gifts'] = !empty($instance->checkGiftsAvailable($decoded_rule));

            if ($decoded_rule['is_cart_sum']) {
                $decoded_rule['count_products'] = 0;
            } else {
                $collection = new shopProductsCollection('giftrule/' . $rule['id'] . '?ignore_gifts=1');
                $decoded_rule['count_products'] = $collection->count();
            }

            $revert = $instance->getRevertTextFromRule($decoded_rule);
            $decoded_rule['revert_placeholder'] = html_entity_decode(strip_tags($revert));

            $result[] = $decoded_rule;
        }

        $this->response = array('rules' => $result);
    }


}