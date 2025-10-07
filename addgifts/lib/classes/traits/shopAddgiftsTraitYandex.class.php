<?php

/**
 * расширение: работа в админке
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitYandex
{

    /**
     * Хук => промо акции
     * @return array
     */
    public function promoRules($params)
    {

        if (!$this->isEnable()) {
            return;
        }

        if (!$this->getSettings('export')) {
            return;
        }

        $rules = shopAddgiftsRulesModel::getRules();

        $result = array();

        foreach ($rules as $rule) {

            $rule['gifts'] = $this->checkGiftsAvailable($rule);


            if (empty($rule['gifts'])) {
                continue;
            }

            $required_quantity = 1;

            if ($rule['gift_limit'] == 'n') {
                $required_quantity = empty($rule['limit_count']) ? 1 : (int)$rule['limit_count'];
                if (empty($required_quantity)) {
                    $required_quantity = 1;
                }
            }

            $gift_ids = array();
            foreach ($rule['gifts'] as $gift) {
                $gift_ids[] = $gift['product']['id'];
            }

            //Перестраховка
            if (empty($gift_ids)) {
                continue;
            }

            $gifts_hash = 'id/' . implode(',', $gift_ids);

            $promo = array(
                'type' => shopImportexportHelper::PROMO_TYPE_GIFT,
                'settings' => '?action=products#/addgifts/' . $rule['id'],
                'source' => 'Подарки: ' . (!empty($rule['name']) ? $rule['name'] : 'ID: ' . $rule['id']), # Internal name
                'hash' => 'giftrule/' . $rule['id'], # shop products collection hash
                'required_quantity' => $required_quantity,  # Minimal required items quantity
                'gifts_hash' => $gifts_hash, # shop products collection hash (for PROMO_TYPE_GIFT)
            );


            if (!empty($rule['export_name'])) {
                $name = $rule['export_name'];
            } else {

                $name = empty($rule['text']) ? '' : $rule['text'];

                if (($rule['gift_limit'] == 'n')) {
                    $name = empty($rule['stimul_product']) ? '' : $rule['stimul_product'];
                }
            }

            try {
                $view = wa()->getView();

                $view->assign('need_buy', $required_quantity);
                $view->assign('rule', $rule);

                $name = $view->fetch('string:' . $name);

            } catch (Exception $e) {
                waLog::log('Ошибка компиляции имени: ' . $name, 'addgifts.log');
                $name = '';
            }

            $promo['name'] = $name;

            if (!empty($rule['export_description'])) {
                $promo['description'] = $rule['export_description'];
            }


            $result[$rule['id']] = $promo;
        }

        return $result;
    }


}