<?php
/**
 *
 * Ручное добавление заказа
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopAddgiftsPluginBackendOrdereditController extends waJsonController
{
    public function execute()
    {
        $this->response = "none";

        $items = waRequest::post('items', array(), waRequest::TYPE_ARRAY);

        if (empty($items)) {
            return;
        }


        $instance = shopAddgiftsPlugin::getInstance();

        //Плагин установлен и включен
        if (!$instance->isEnable()) {
            return;
        }

        $sum_in_cart = -1;

        $grouped_rules = $instance->getCartGifts($items, $sum_in_cart);

        $have_gifts = 0;
        $new_items = array();

        $gift_price = $instance->getSettings('gift_price');
        $gift_price = floatval(str_replace(',', '.', trim($gift_price)));


        foreach ($grouped_rules as $vid) {
            foreach ($vid as $rule) {
                if (empty($rule['need_buy'])) {
                    $have_gifts = 1;


                    foreach ($rule['gifts'] as $gift) {

                        $new_name = $gift['product']['name'];
                        if (!empty($gift['selected_sku_name'])) {
                            $new_name .= ' (' . $gift['selected_sku_name'] . ')';
                        }
                        $new_name .= ' (Подарок)';

                        //В админке SKU не учитывается так как его можнос менить
                        $gift_ident = $gift['product']['id'];// . '-' . $gift['selected_sku'];

                        if (!isset($new_items[$gift_ident])) {

                            $new_items[$gift_ident] = array(
                                'name' => $new_name,
                                'product_id' => $gift['product']['id'],
                                'sku_id' => $gift['selected_sku'],

                                'type' => 'product',

                                'price' => $gift_price,
                                'quantity' => $gift['gift_count'],
                            );
                        } else {
                            $new_items[$gift_ident]['quantity'] += $gift['gift_count'];
                        }
                    }

                }
            }
        }

        //Перебираем заказ
        foreach ($items as $key => $cart_item) {

            $rules = isset($grouped_rules[$cart_item['id']]) ? $grouped_rules[$cart_item['id']] : array();

            $html = $instance->inBackendOrderItem($rules, $cart_item['id']);

            if (!empty($cart_item['cart_custom_html'])) {
                $html = $cart_item['cart_custom_html'] . $html;
            }

            $items[$key]['html'] = $html;
        }

        //Правила после
        $rules = isset($grouped_rules['after']) ? $grouped_rules['after'] : array();
        $after_items = $instance->inBackendOrderItem($rules, 'after');

        $this->response = array('after_items' => $after_items, 'items' => $items, 'have_gifts' => $have_gifts, 'gifts' => $new_items);

    }


}