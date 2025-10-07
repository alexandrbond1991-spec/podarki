<?php

/**
 * расширение: обновление
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitUpdater
{

    /**
     * Преобразование настроек для витрины с формата 1.6 до 2.0
     * @param $route
     * @param $new
     * @return mixed
     */
    public function _convertOldRoute($route, $new)
    {

        $new['enable'] = $route['enable'];

        $new['hook'] = $route['hook'];

        $new['badge_text'] = $route['badge_text'];
        $new['badge_text_many'] = $route['badge_text_many'];
        $new['many_badges'] = $route['many_badges'];

        $new['product_image'] = $route['product_image'];
        $new['product_image_width'] = $route['product_image_width'];

        $new['cart_product_image'] = $route['cart_product_image'];
        $new['cart_product_image_width'] = $route['cart_product_image_width'];

        if (!empty($route['text_color'])) {
            $new['text_color'] = $route['text_color'];
        }
        if (!empty($route['link_color'])) {
            $new['link_color'] = $route['link_color'];
        }
        if (!empty($route['link_color_hover'])) {
            $new['hover_color'] = $route['link_color_hover'];
        }
        if (!empty($route['hint_color'])) {
            $new['hint_color'] = $route['hint_color'];
        }
        if (!empty($route['background_color'])) {
            $new['background_color'] = $route['background_color'];
        }
        if (!empty($route['badge_background_color'])) {
            $new['badge_background_color'] = $route['badge_background_color'];
        }
        if (!empty($route['badge_color'])) {
            $new['badge_color'] = $route['badge_color'];
        }

        if (!empty($route['cart_text_color'])) {
            $new['cart_text_color'] = $route['cart_text_color'];
        }
        if (!empty($route['cart_link_color'])) {
            $new['cart_link_color'] = $route['cart_link_color'];
        }
        if (!empty($route['cart_link_color_hover'])) {
            $new['cart_hover_color'] = $route['cart_link_color_hover'];
        }
        if (!empty($route['cart_hint_color'])) {
            $new['cart_hint_color'] = $route['cart_hint_color'];
        }
        if (!empty($route['cart_background_color'])) {
            $new['cart_background_color'] = $route['cart_background_color'];
        }

        if (!empty($route['img_link'])) {
            $new['gift_image'] = $route['img_link'];
        }
        if (!empty($route['cart_img_link'])) {
            $new['cart_gift_image'] = $route['cart_img_link'];
        }

        return $new;
    }

    /**
     * Обновление с 1.6 до 2.0
     */
    public function updateTo20()
    {
        //Обновляем базу данных
        try {

            $model = new waModel();

            $model->query("CREATE TABLE `shop_addgifts__rules` (
                `id` int(11) NOT NULL AUTO_INCREMENT, `rule` mediumtext NOT NULL,
                `sort` int(11) NOT NULL DEFAULT '0', `status` int(11) NOT NULL DEFAULT '1',
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

            $model->query("CREATE TABLE `shop_addgifts__storefronts` (
                `id` int(11) NOT NULL AUTO_INCREMENT, `storefront` varchar(255) DEFAULT NULL, `value` mediumtext, 
                PRIMARY KEY (`id`), KEY `storefront` (`storefront`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");


        } catch (Exception $e) {
            waLog::log('Ошибка обновления базы данных: ' . $e->getMessage(), 'addgifts.log');
        }

        try {
            //Обновление настроек
            $settings = $this->getSettings();

            //Преобразование витрин
            $routes = $this->getRoutes();
            $list_routes = array();
            foreach ($routes as $domain) {
                foreach ($domain['routing'] as $routing) {
                    $list_routes[md5($routing['value'])] = $routing['value'];
                }
            }

            $old_texts = array();

            //Перенос настроек
            if (!empty($settings['routes'])) {

                $default = include $this->path . '/lib/config/storefront_default.php';
                $default['gift_image'] = $this->getPluginStaticUrl() . 'img/gift.png';
                $default['cart_gift_image'] = $this->getPluginStaticUrl() . 'img/gift.png';

                $storefronts = array();

                foreach ($settings['routes'] as $route_key => $route) {
                    if ($route_key == '0') {
                        $old_texts['text'] = $route['text'];
                        $old_texts['hint'] = $route['hint'];
                        $old_texts['price_comment'] = $route['price_comment'];

                        $storefronts['0'] = $this->_convertOldRoute($route, $default);
                    } elseif (isset($list_routes[$route_key])) {
                        $storefronts[$list_routes[$route_key]] = $this->_convertOldRoute($route, $default);
                    }
                }
                $this->saveStorefronts($storefronts);
            } else {
                $this->createDefaultStorefrontVariables();
            }

            $model = new shopAddgiftsPluginModel();

            $all_gifts = $model->getAll();

            $prepared_gifts = array();

            foreach ($all_gifts as $gift) {
                //Продукты
                if (!empty($gift['product_id'])) {

                    $index = 'p' . $gift['product_id'];
                } else {
                    $index = 'c' . $gift['category_id'];
                }

                if (!isset($prepared_gifts[$index])) {
                    $prepared_gifts[$index] = $gift;
                    $prepared_gifts[$index]['gifts'] = array();
                }

                $prepared_gifts[$index]['gifts'][$gift['gift_id']] = $gift['gift_id'];
            }

            if (empty($prepared_gifts)) {
                return;
            }

            //Собираем подарки
            foreach ($prepared_gifts as $key => $prepared_gift) {
                sort($prepared_gift['gifts']);
                $gifts = array_values($prepared_gift['gifts']);
                $prepared_gifts[$key]['gift_ids'] = implode(',', $gifts);
            }

            //Собираем получившиеся правила
            $prepared_rules = array();
            foreach ($prepared_gifts as $key => $prepared_gift) {
                if (!isset($prepared_rules[$prepared_gift['gift_ids']])) {
                    $prepared_rules[$prepared_gift['gift_ids']] = array();
                }

                $prepared_rules[$prepared_gift['gift_ids']][] = $prepared_gift;
            }

            $sort = 1;

            $model = new shopAddgiftsRulesModel();

            foreach ($prepared_rules as $gifts => $new_rule) {

                $group = array('is_stop' => '', 'type' => 'group', 'items' => array(), 'op' => 'or');

                foreach ($new_rule as $operation) {

                    $item = array(
                        'type' => 'item',
                        'operation' => '=',
                        'op_type' => !empty($operation['product_id']) ? 'product' : 'category',
                    );

                    if (!empty($operation['product_id'])) {
                        $item['product'] = $operation['product_id'];
                    } else {
                        $item['category'] = $operation['category_id'];

                        if (!empty($settings['more_category'])) {
                            $item['with_childs'] = '1';
                        }
                    }

                    $group['items'][] = $item;
                }

                $rule = array(
                    'group' => array('type' => 'group', 'op' => 'and', 'items' => array($group)),
                    'gift_limit' => 'all',
                    'limit_product' => 'one',
                    'limit_count' => 2,
                    'text' => $old_texts['text'],
                    'hint' => $old_texts['hint'],
                    'export_name' => $old_texts['text'],
                    'export_description' => '',

                    'stimul_product' => 'Купите {$rule.limit_count} шт. и получите в подарок:',
                    'stimul' => 'Купите еще {$need_buy} шт. и получите в подарок:',

                    'gifts' => array()
                );

                if (!empty($settings['only_available'])) {
                    $rule['group']['items'][] =
                        array("type" => "item", "op_type" => "properties", "operation" => ">", "operation_type_id" => "=", "field" => "count", "value" => "0");
                }


                $ids = explode(',', $gifts);
                foreach ($ids as $gift_id) {

                    $gift = array(
                        'guid' => rand(),
                        'sku' => '',
                        'count' => '1',
                        'product' => $gift_id
                    );

                    $rule['gifts'][] = $gift;
                }

                $data = array('status' => 1, 'sort' => $sort++);
                $data['rule'] = json_encode($rule);

                $model->insert($data);
            }

        } catch (Exception $e) {
            waLog::log('Ошибка обновления плагина: ' . $e->getMessage(), 'addgifts.log');
        }

    }

}