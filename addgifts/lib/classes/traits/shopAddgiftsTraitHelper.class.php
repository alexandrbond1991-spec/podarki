<?php

/**
 * расширение: основной функционал плагина
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitHelper
{


    public static $_cache_check_gifts_available = array();
    public static $checked_gifts_in_order = array();
    public static $ignore_stock_count = null;
    public $present_rules = array();
    public $last_rule_result = false;
    public $cart_to_order = array();
    public $product_to_order = array();

    /**
     * Вывод подарка к товару
     *
     * @param $product
     * @param bool $route
     * @return bool|string
     */
    public static function inProduct($product, $route = false)
    {

        /** @var shopAddgiftsPlugin $instance */
        $instance = self::getInstance();

        //Плагин установлен и включен
        if (!$instance->isEnable()) {
            return false;
        }

        //Настройки витрины
        $settings = $instance->getStorefrontSettings($route);

        //Плагин включен на данной витрине
        if (empty($settings['enable'])) {
            return false;
        }

        $vid = 'p' . $product['id'];

        //Нормализуем продукт
        $product = $instance->normalizeShopProduct($product);

        //Получаем подарки для товара
        $rules = $instance->getProductGifts($product);

        $rules = $instance->prepareProductRules($rules, $vid);

        if (!empty($rules)) {
            $variables = array('rules' => $rules, 'vid' => $vid);
            return $instance->parseTemplate('product.html', $variables, $settings);
        }

        //Обратная связь
        $params = $product->params;

        $show_revert = $instance->getSettings('show_revert');
        if (isset($params['addgifts__revert'])) {
            $show_revert = $params['addgifts__revert'];
        }

        if (empty($show_revert)) {
            return false;
        }

        $rules = shopAddgiftsRulesModel::getRules();
        if (empty($rules)) {
            return false;
        }


        $revers_rules = array();

        foreach ($rules as $rule) {

            //Стоп правила
            if (!empty($rule['is_stop'])) {
                continue;
            }

            $rule['gifts'] = $instance->checkGiftsAvailable($rule);
            if (empty($rule['gifts'])) {
                continue;
            }

            foreach ($rule['gifts'] as $gift) {
                if ($gift['product']['id'] == $product['id']) {
                    $revers_rules[] = $rule;
                    break;
                }
            }
        }

        if (empty($revers_rules)) {
            return false;
        }

        foreach ($revers_rules as &$rule) {
            if (empty($rule['revert'])) {
                $rule['revert'] = $instance->getRevertTextFromRule($rule);
            }
        }
        unset($rule);


        $variables = array('rules' => $revers_rules);
        return $instance->parseTemplate('revert.html', $variables, $settings);
    }

    /**
     * Возвращает подарки товара (основная функция)
     *
     * @param $product
     * @return array
     */
    public function getProductGifts($product)
    {

        $result = array();

        //Нормализуем продукт в shopProduct
        $product = $this->normalizeShopProduct($product);

        if (empty($product)) {
            return $result;
        }

        //Получаем список правил
        $rules = shopAddgiftsRulesModel::getRules();
        if (empty($rules)) {
            return $result;
        }

        $this->last_rule_result = false;

        foreach ($rules as $rule) {

            //Если нет подарков не проверяем
            $rule['gifts'] = $this->checkGiftsAvailable($rule);

            if (empty($rule['is_stop']) && empty($rule['gifts'])) {
                $this->last_rule_result = false;
                continue;
            }

            //Условия не выполнены
            $correct_rule = $this->checkCondition($product, $rule['group'], $result);

            //Запоминаем последнее правило
            $this->last_rule_result = $correct_rule;

            //Обычные правила
            if (empty($rule['is_stop'])) {

                if (!$correct_rule) {
                    continue;
                }

                $result[] = $rule;
            } else {

                //Сработало стоп правило
                if ($correct_rule) {

                    if ($rule['action'] == 'all') {
                        $result = array();
                        break;
                    } elseif ($rule['action'] == 'stop') {
                        break;
                    } elseif ($rule['action'] == 'reset') {
                        $result = array();
                    }
                }
            }
        }


        return $result;
    }

    /**
     * Проверяет подарки на удаление, доступность на витрине, наличие на складе
     *
     * @param $rule
     * @return array
     */
    public function checkGiftsAvailable($rule)
    {
        $result = array();

        if (empty($rule['gifts'])) {
            return $result;
        }

        $id_rule = empty($rule['id']) ? 0 : (int)$rule['id'];

        if (isset(self::$_cache_check_gifts_available[$id_rule])) {
            return self::$_cache_check_gifts_available[$id_rule];
        }


        foreach ($rule['gifts'] as $key => $gift) {

            //Товар не указан в настройках
            if (empty($gift['product'])) {
                continue;
            }

            $gift_product = new shopProduct((int)$gift['product']);

            //Товар был удален
            if (empty($gift_product['id'])) {
                continue;
            }

            //Проверяем наличие подарка (тип товара) на самой витрине
            if (wa()->getEnv() == 'frontend') {
                if ($types = waRequest::param('type_id')) {
                    if (!in_array($gift_product['type_id'], (array)$types)) {
                        continue;
                    }
                }
            }

            //Наличие на складе
            $ignore_stock_count = self::getIgnoreStockCount();
            if (!$ignore_stock_count) {
                //Если пользователь может выбирать артикуль то проверяем только общее наличие товара
                if (!empty($gift['may_change_sku'])) {
                    if (!is_null($gift_product['count']) && (empty((double)$gift_product['count']))) {
                        continue;
                    }
                } else {
                    $sku = empty($gift['sku']) ? $gift_product['sku_id'] : $gift['sku'];

                    //Нужного артикуля нет
                    if (empty($gift_product['skus'][$sku])) {
                        continue;
                    }

                    $count = $gift_product['skus'][$sku]['count'];

                    if (!is_null($count) && empty((double)$count)) {
                        continue;
                    }
                }
            }

            //ссылка на товар
            if (wa()->getEnv() != 'backend') {
                $category_url = ifset($gift_product, 'category_url', '');

                $gift['frontend_url'] = wa()->getRouteUrl(
                    'shop/frontend/product',
                    array('product_url' => $gift_product['url'], 'category_url' => $category_url)
                );
            }
            $gift['product'] = $gift_product;
            $result[] = $gift;
        }

        self::$_cache_check_gifts_available[$id_rule] = $result;

        return $result;
    }

    public static function getIgnoreStockCount()
    {
        if (is_null(self::$ignore_stock_count)) {
            self::$ignore_stock_count = wa('shop')->getConfig()->getGeneralSettings('ignore_stock_count');
        }

        return self::$ignore_stock_count;
    }

    public function checkCondition($product, $group, $present_rules = null)
    {
        if (!is_null($present_rules)) {
            $this->present_rules = $present_rules;
        }


        //По умолчанию верное условие
        if (empty($group['items'])) {
            return true;
        }


        $result = null;

        //Перебираем условия
        foreach ($group['items'] as $item) {

            if ($item['type'] == 'group') {
                $item_result = $this->checkCondition($product, $item);
            } else {
                $item_result = $this->checkItemCondition($product, $item);
            }

            //любое условие равно
            if ($group['op'] == 'or') {

                if ($item_result == true) {
                    return true;
                } else {
                    $result = $item_result;
                }
            } else {
                if ($item_result == false) {
                    return false;
                } else {
                    $result = $item_result;
                }
            }
        }

        return $result;
    }

    public function checkItemCondition($product, $item)
    {
        $result = true;
        if (empty($item['op_type'])) {
            return $result;
        }

        $method = 'checkItemCondition_' . ucfirst($item['op_type']);
        if (method_exists($this, $method)) {
            $result = $this->$method($product, $item);
        } else {
            waLog::log('Отсутсвует метод: ' . $method, 'addgifts.log');
        }

        return $result;
    }

    public function prepareProductRules($rules, $vid)
    {
        foreach ($rules as $rule_key => $rule) {
            $rules[$rule_key]['gifts'] = $this->prepareGifts($rule, $vid);
        }

        return $rules;
    }

    public function prepareGifts($rule, $vid)
    {

        $cookie_name = 'addgifts__gift_p_' . $vid . '_r_' . $rule['id'];

        //Выбор в корзине
        $selected_gift = waRequest::cookie($cookie_name, 0);

        //Выбор в заказе
        if (empty($selected_gift) && (isset($this->cart_to_order[$vid]))) {
            $cookie_name = 'addgifts__gift_p_' . $this->cart_to_order[$vid] . '_r_' . $rule['id'];
            $selected_gift = waRequest::cookie($cookie_name, 0);
        }

        //Выбранный подарок и артикул 
        $is_one = false;
        if (!empty($rule['multiple'])) {
            $is_one = ($rule['multiple'] == 'one');
        }

        foreach ($rule['gifts'] as $key => $gift) {
            $rule['gifts'][$key] = $this->prepareGift($gift, $vid, $rule, $key);

            if ($is_one) {
                $rule['gifts'][$key]['selected'] = ($selected_gift == $key);
            }
        }

        return $rule['gifts'];
    }

    public function prepareGift($gift, $vid, $rule, $gift_index)
    {

        if ($gift['product']['sku_count'] > 1) {

            $selected_sku = $gift['product']['sku_id'];

            if (!empty($gift['sku'])) {
                $selected_sku = $gift['sku'];
            }

            //Выбор пользователя при разрешение
            if (!empty($gift['may_change_sku'])) {

                $cookie_name = 'addgifts__sku_vid_' . $vid . '_r_' . $rule['id'] . '_gi_' . $gift_index;

                //Выбор в корзине
                $selected_in_cart = waRequest::cookie($cookie_name, null);

                //Выбор в заказе
                if (is_null($selected_in_cart) && (isset($this->cart_to_order[$vid]))) {
                    $cookie_name = 'addgifts__sku_vid_' . $this->cart_to_order[$vid] . '_r_' . $rule['id'] . '_gi_' . $gift_index;
                    $selected_in_cart = waRequest::cookie($cookie_name, null);
                }

                //Выбор на странице товара
                if (is_null($selected_in_cart) && (isset($this->product_to_order[$vid]))) {
                    $cookie_name = 'addgifts__sku_vid_' . $this->product_to_order[$vid] . '_r_' . $rule['id'] . '_gi_' . $gift_index;
                    $selected_in_cart = waRequest::cookie($cookie_name, null);
                }

                $selected_sku = is_null($selected_in_cart) ? $selected_sku : $selected_in_cart;
            }

            if (!empty($gift['product']['skus'][$selected_sku])) {
                $sku = $gift['product']['skus'][$selected_sku];
            } else {
                $sku = $gift['product']['skus'][$gift['product']['sku_id']];
            }

            //Выбранный артикул
            $gift['selected_sku'] = $sku['id'];
            $gift['selected_sku_name'] = empty($sku['name']) ? $sku['id'] : $sku['name'];
        } else {
            $gift['selected_sku'] = $gift['product']['sku_id'];
        }

        return $gift;
    }

    /**
     * Общий списка подарков в корзине/заказе
     * @param bool $route
     * @return string
     * @throws waException
     */
    public static function inCartFull($route = false)
    {

        /** @var shopAddgiftsPlugin $instance */
        $instance = self::getInstance();

        //Плагин установлен и включен
        if (!$instance->isEnable()) {
            return false;
        }

        //Настройки витрины
        $settings = $instance->getStorefrontSettings($route);

        //Плагин включен на данной витрине
        if (empty($settings['enable'])) {
            return false;
        }

        $grouped_rules = $instance->getCartGifts();

        $after = isset($grouped_rules['after']) ? $grouped_rules['after'] : array();
        return $instance->inCartItem($after, 'after');
    }

    public function getCartGifts($items = null, $sum_in_cart = null)
    {

        $result = array();
        $after_rules = array();

        if (is_null($items)) {
            $cart = new shopCart();
            $items = $cart->items();

            $sum_in_cart = $cart->total();

            //Учет выбранной валюты на витрине
            $default_currency = wa('shop')->getConfig()->getCurrency(true);
            $frontend_currency = wa('shop')->getConfig()->getCurrency(false);

            if ($default_currency != $frontend_currency) {
                $sum_in_cart = 0 + shop_currency($sum_in_cart, $frontend_currency, $default_currency, false);
            }
        }

        //Сбрасываем значения (для защиты от двойного вызова)
        self::$checked_gifts_in_order = array();

        //Перебираем корзину
        foreach ($items as $key => $cart_item) {

            if ($cart_item['type'] != 'product') {
                continue;
            }

            $rules = $this->getProductGifts($cart_item['product_id']);

            if (!empty($rules)) {

                foreach ($rules as $key_rule => $rule) {


                    $rule['cart_item'] = $cart_item;

                    $limit_count = empty($rule['limit_count']) ? 0 : (int)$rule['limit_count'];

                    //Правило не касается одного конкретного товара - переносим вниз
                    if ((!empty($this->getSettings('group_rules'))) || (($rule['gift_limit'] == 'n') && ($limit_count > 1) && ($rule['limit_product'] == 'all'))) {

                        $after_rules[] = $rule;
                        unset($rules[$key_rule]);
                    } else {
                        $rules[$key_rule] = $rule;
                    }
                }

                if (!empty($rules)) {
                    $result[$cart_item['id']] = $rules;
                }
            }
        }

        //Правила по сумме заказа
        $cart_rules = shopAddgiftsRulesModel::getRules(true);
        if (!empty($cart_rules) && ($sum_in_cart != -1)) {

            $result_cart = array();
            foreach ($cart_rules as $rule) {

                //Если нет подарков не проверяем
                $rule['gifts'] = $this->checkGiftsAvailable($rule);

                if (empty($rule['gifts'])) {
                    continue;
                }

                //Условия не выполнены
                $correct_rule = $this->checkCartCondition($rule, $sum_in_cart);

                if ($correct_rule) {
                    $after_rules[] = $rule;
                }
            }
        }

        //Правила после
        if (!empty($after_rules)) {

            //Группируем правила
            $grouped_rules = array();
            foreach ($after_rules as $rule) {
                if (isset($grouped_rules[$rule['id']])) {
                    $grouped_rules[$rule['id']]['cart_item']['quantity'] += $rule['cart_item']['quantity'];
                } else {
                    $grouped_rules[$rule['id']] = $rule;
                }
            }

            $result['after'] = $grouped_rules;
        }

        $result = $this->prepareCartRules($result, $items, $sum_in_cart);

        //Ограничение на вывод акций по сумме в корзине
        $cart_limit = (int)$this->getSettings('cart_limit');
        if (!empty($cart_limit)) {
            foreach ($result as $vid => $rules) {
                $index = 0;
                foreach ($rules as $key => $item) {
                    if ((!empty($item['is_cart_sum'])) && (!empty($item['need_buy']))) {
                        if (++$index > $cart_limit) {
                            unset($result[$vid][$key]);
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function checkCartCondition($rule, $sum_in_cart)
    {

        $min = (!empty($rule['min'])) ? (int)$rule['min'] : 0;
        $max = (!empty($rule['max'])) ? (int)$rule['max'] : 0;

        //Нет условий
        if (empty($max) && empty($min)) {
            return false;
        }

        if (!empty($max)) {
            return ($sum_in_cart <= $max);
        }

        return true;
    }

    public function prepareCartRules($grouped_rules, $items, $sum_in_cart = 0)
    {

        //Проверяем хватит ли подарков
        $ignore_stock_count = self::getIgnoreStockCount();

        foreach ($grouped_rules as $vid => $rules) {

            foreach ($rules as $rule_key => $rule) {

                $rule['gifts'] = $this->prepareGifts($rule, $vid);

                $limit_count = empty($rule['limit_count']) ? 0 : (int)$rule['limit_count'];
                $need_buy = 0;

                if ($rule['is_cart_sum']) {
                    if (!empty($rule['min']) && ($rule['min'] > $sum_in_cart)) {
                        $need_buy = $rule['min'] - $sum_in_cart;

                        $default_currency = wa('shop')->getConfig()->getCurrency(true);
                        $frontend_currency = wa('shop')->getConfig()->getCurrency(false);

                        $need_buy = shop_currency($need_buy, $default_currency, $frontend_currency, true);
                    }
                } else {
                    if (($rule['gift_limit'] == 'n') && ($limit_count > 1) && ($limit_count > $rule['cart_item']['quantity'])) {
                        $need_buy = $limit_count - $rule['cart_item']['quantity'];
                    }
                }

                $rule['need_buy'] = $need_buy;

                foreach ($rule['gifts'] as $gift_key => $gift) {

                    $gift['original_count'] = $gift['count'];

                    if (empty($need_buy)) {

                        if ($rule['is_cart_sum']) {
                            $count = $gift['count'];
                        } else {
                            $count = $gift['count'] * $rule['cart_item']['quantity'];

                            if (($limit_count > 1) && ($rule['gift_limit'] == 'n')) {
                                $count = $gift['count'] * floor($rule['cart_item']['quantity'] / $limit_count);
                            }
                        }

                        if ((!empty($gift['limit'])) && ($count > (int)$gift['limit'])) {
                            $count = $gift['limit'];
                        }

                        $actual_count = $count;
                    } else {
                        $count = $gift['count'];
                        $actual_count = 0;
                    }

                    $gift['count'] = $count;
                    $gift['actual_count'] = $actual_count;

                    //Если подарков конечное кол-во
                    if ((!$ignore_stock_count) && ($gift['product']['skus'][$gift['selected_sku']]['count'] !== null)) {
                        $gift = self::checkAllowGiftInOrder($gift, $items);
                    } else {
                        $gift['gift_count'] = $gift['count'];
                    }

                    $rule['gifts'][$gift_key] = $gift;
                }

                $grouped_rules[$vid][$rule_key] = $rule;
            }
        }

        return $grouped_rules;
    }

    public static function checkAllowGiftInOrder($gift, $items)
    {

        $product = $gift['product'];

        $gift_ident = $product['id'] . '-' . $gift['selected_sku'];

        //Первое вхождение подарка
        if (!isset(self::$checked_gifts_in_order[$gift_ident])) {

            $limits = array(
                'gift_count' => $product['skus'][$gift['selected_sku']]['count']
            );

            //Вычитаем которые уже лежат в корзине (с учетом sku)
            foreach ($items as $item) {
                if ($item['type'] != 'product') {
                    continue;
                }

                if (($item['product_id'] == $product['id']) && ($item['sku_id'] == $gift['selected_sku'])) {
                    $limits['gift_count'] -= (int)$item['quantity'];
                }
            }
        } else {
            $limits = self::$checked_gifts_in_order[$gift_ident];
        }

        //Если вообще нет в наличие
        if ($limits['gift_count'] <= 0) {
            $gift['gift_count'] = 0;
        } else {

            //подарков не хватает
            if ($gift['actual_count'] > $limits['gift_count']) {
                $gift['gift_count'] = $limits['gift_count'];
                $limits['gift_count'] = 0;
            } else {
                $limits['gift_count'] -= (int)$gift['actual_count'];
                $gift['gift_count'] = $gift['count'];
            }
        }

        self::$checked_gifts_in_order[$gift_ident] = $limits;

        return $gift;
    }

    public function inCartItem($rules, $vid, $settings = null)
    {
        if (is_null($settings)) {
            $settings = $this->getStorefrontSettings();
        }

        $variables = array('rules' => $rules, 'vid' => $vid);
        return $this->parseTemplate('cart_item.html', $variables, $settings);
    }

    public static function inCart($item, $route = false)
    {
        /** @var shopAddgiftsPlugin $instance */
        $instance = self::getInstance();

        //Плагин установлен и включен
        if (!$instance->isEnable()) {
            return '';
        }

        //Настройки витрины
        $settings = $instance->getStorefrontSettings($route);

        //Плагин включен на данной витрине
        if (empty($settings['enable'])) {
            return '';
        }

        $grouped_rules = $instance->getCartGifts();

        $after = isset($grouped_rules[$item['id']]) ? $grouped_rules[$item['id']] : array();
        return $instance->inCartItem($after, $item['id'], $settings);
    }

    public function checkItemCondition_Product($product, $item)
    {
        if (empty($item['product'])) {
            return 'true';
        } else {
            $result = ($product['id'] == $item['product']);
            return ($item['operation'] == '<>') ? !$result : $result;
        }
    }

    public function checkItemCondition_Category($product, $item)
    {
        if (empty($item['category'])) {
            return true;
        }

        //У товара нет категорий
        if (empty($product['category_id'])) {
            return false;
        }

        $category_model = new shopCategoryModel();
        $need_category = $category_model->getById($item['category']);

        if (empty($need_category)) {
            return false;
        }

        //Статичная категория
        if ($need_category['type'] == shopCategoryModel::TYPE_STATIC) {

            $product_categories = $product['categories'];
            if (isset($product_categories[$item['category']])) {
                return ($item['operation'] == '<>') ? false : true;
            }

            //Проверяем в дочерних категориях
            if (!empty($item['with_childs'])) {

                $find_ids = array_keys($product_categories);
                $operation = $item['operation'] == '<>' ? 'NOT IN' : 'IN';

                $found = $category_model->descendants($item['category'])->where('id ' . $operation . ' (' . implode(',', $find_ids) . ')')->query()->count();

                if ($found) {
                    return true;
                }
            }
        } else {

            //            //Динамичеcкая категория - пока отключены
            //            $collection = new shopProductsCollection('category/' . (int)$item['category']);
            //            $found = $collection->addWhere('p.id=' . (int)$product['id'])->count();
            //            if ($found) {
            //                return true;
            //            }
            return false;
        }

        return false;
    }

    public function checkItemCondition_Set($product, $item)
    {
        if (empty($item['set'])) {
            return true;
        }

        $sets = $product->sets;

        $operation = ($item['operation'] == '<>') ? '<>' : '=';
        $default_result = ($operation == '=') ? false : true;

        //У товара нет требуемого списка
        if (!empty($sets) && !empty($sets[$item['set']])) {
            return !$default_result;
        } else {
            return $default_result;
        }
    }

    public function checkItemCondition_Properties($product, $item)
    {
        if (empty($item['field'])) {
            return true;
        }

        $operation = '=';
        $value = $product[$item['field']];

        switch ($item['field']) {

            case 'price':
            case 'min_price':
            case 'max_price':
                $operation = $item['operation'];
                break;

            case 'count':

                $need_count = $item['value'];
                $operation = $item['operation'];

                //Крайний случай бесконечность
                if ($need_count == '') {

                    switch ($operation) {
                        case '=':
                            return is_null($value);

                        case '>':
                            return false;

                        case '<':
                            return !is_null($value);

                        case '>=':
                        case '<=':
                            return is_null($value);

                        case '<>':
                            return !is_null($value);
                    }
                }

                //Второй крайний случай
                if (is_null($value)) {
                    switch ($operation) {
                        case '=':
                        case '<>':
                        case '<':
                        case '<=':
                            return false;

                        case '>':
                        case '>=':
                            return true;
                    }
                }
                break;

            case 'type_id':
                $item['value'] = $item['value_type_id'];
                $operation = $item['operation_type_id'];
                break;

            case 'compare_price-price':
                $value = $product['compare_price'] - $product['price'];
                $operation = $item['operation'];
                break;
        }

        if (empty($value)) {
            $value = 0;
        }

        if (empty($item['value'])) {
            $item['value'] = 0;
        }

        switch ($operation) {
            case '=':
                return ($value == $item['value']);

            case '>':
                return ($value > $item['value']);

            case '<':
                return ($value < $item['value']);

            case '>=':
                return ($value >= $item['value']);

            case '<=':
                return ($value <= $item['value']);

            case '<>':
                return ($value != $item['value']);
        }

        return true;
    }

    public function checkItemCondition_Rules($product, $item)
    {
        if (empty($item['rule_status'])) {
            return true;
        }

        switch ($item['rule_status']) {

                //Выполнено любое предыдущее правило
            case 'exist':
                return !empty($this->present_rules);

                //Не выполнено ни одно из предыдущих правил
            case 'not_exist':
                return empty($this->present_rules);

                //Выполнено непосредтсвено предыдущее правило
            case 'one_exist':
                return $this->last_rule_result;

                //Не выполнено непосредтсвено предыдущее правило
            case 'not_one_exist':
                return !$this->last_rule_result;

            default:
                return false;
        }
    }

    /**
     * Хук => При создание заказа добавляем подарки
     * @param $params
     */
    public function orderActionCreate($params)
    {
        //Автоматически добавляем только с витрины при включенном плагине
        if (($this->isEnable()) && (wa()->getEnv() == 'frontend')) {
            $this->orderCreate($params);
        }
    }

    public function orderCreate($params)
    {

        $order_params = new shopOrderParamsModel;
        $settings = $this->getStorefrontSettings();


        if (empty($settings['enable'])) {
            return;
        }

        $order_model = new shopOrderModel();
        $order_data = $order_model->getOrder($params['order_id']);

        $currency = wa('shop')->getConfig()->getCurrency(true);

        $model = new shopOrderItemsModel();
        $items = $model->getItems($params['order_id']);

        //Получаем идентификаторы Корзины -> Заказа
        $this->cartToOrderTransfer($items);

        $sum_in_cart = 0;
        foreach ($items as $item) {
            $sum_in_cart += ($item['price'] * $item['quantity']) - $item['total_discount'];
        }

        $grouped_rules = $this->getCartGifts($items, $sum_in_cart);

        $new_items = array();
        $to_log = array();

        foreach ($grouped_rules as $cart_id => $vid) {

            $product_name = null;

            $rule_names = array();
            $gift_names = array();

            foreach ($vid as $rule) {

                if (!empty($rule['need_buy'])) {
                    continue;
                }

                if ($rule['is_cart_sum']) {
                    $product_name = 'По сумме заказа';
                } else {
                    $product_name = $rule['cart_item']['name'];
                }

                $rule_names[] = !empty($rule['name']) ? $rule['name'] : 'ID: ' . $rule['id'];

                foreach ($rule['gifts'] as $gift_index => $gift) {

                    $is_one = false;
                    if (!empty($rule['multiple'])) {
                        $is_one = ($rule['multiple'] == 'one');
                    }

                    if ($is_one) {
                        $is_selected = ($rule['gifts'][$gift_index]['selected']);
                    }

                    if ((($is_one) && ($is_selected)) || !($is_one)) {

                        $new_name = $gift['product']['name'];
                        if (!empty($gift['selected_sku_name'])) {
                            $new_name .= ' (' . $gift['selected_sku_name'] . ')';
                        }
                        $new_name .= ' (Подарок)';
                        $log_name = $new_name . ' x ' . $gift['gift_count'];

                        if ($gift['gift_count'] != $gift['count']) {
                            $log_name .= ' (нехватает - ' . ($gift['count'] - $gift['gift_count']) . 'шт.)';
                        }


                        $gift_names[] = $log_name;

                        $gift_ident = $gift['product']['id'] . '-' . $gift['selected_sku'];

                        if (!isset($new_items[$gift_ident])) {

                            //Получаем цену подарка из настроке
                            $gift_price = $this->getSettings('gift_price');
                            $gift_price = floatval(str_replace(',', '.', trim($gift_price)));

                            //Учитываем Валюту
                            if ($currency != $order_data['currency']) {
                                $gift_price = shop_currency($gift_price, $currency, $order_data['currency'], false);
                            }

                            $new_items[$gift_ident] = array(
                                'order_id' => $params['order_id'],
                                'name' => $new_name,
                                'product_id' => $gift['product']['id'],
                                'sku_id' => $gift['selected_sku'],
                                'sku_code' => isset($gift['product']['skus'][$gift['selected_sku']]['sku']) ? $gift['product']['skus'][$gift['selected_sku']]['sku'] : '',
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

            if (!is_null($product_name)) {
                $to_log[] = array(
                    'product_name' => $product_name,
                    'vid' => $cart_id,
                    'rule_names' => $rule_names,
                    'gift_names' => $gift_names,
                );
            }
        }

        //Очистка cookie
        $cookies = waRequest::cookie();
        $need_name = 'addgifts__sku_vid_';

        foreach ($cookies as $name => $cookie) {
            if (substr($name, 0, strlen($need_name)) == $need_name) {
                if (substr($name, strlen($need_name), 1) != 'p') {
                    wa()->getResponse()->setCookie($name, null, -1, '/');
                }
            }
        }

        $need_name = 'addgifts__gift_p_';
        foreach ($cookies as $name => $cookie) {
            if (substr($name, 0, strlen($need_name)) == $need_name) {
                wa()->getResponse()->setCookie($name, null, -1, '/');
            }
        }

        //Если нет новых товаров - ничего не делать
        if (empty($new_items)) {
            return;
        }

        //Параметры заказа
        $virtualstock_id = $order_params->getOne($params['order_id'], 'virtualstock_id');
        $stok_id = $order_params->getOne($params['order_id'], 'stock_id');

        //Склады
        $virtualstock = null;
        $stock = null;

        //Если указаны склады
        if ($virtualstock_id) {
            $virtualstock = shopHelper::getStocks(array('v' . $virtualstock_id));
            $virtualstock = array_shift($virtualstock);
        }

        if ($stok_id) {
            $stock = shopHelper::getStocks(array($stok_id));
            $stock = array_shift($stock);
        }

        //Определение складов
        shopAddgiftsWorkflowCreateAction::fillItemsStocks($new_items, $virtualstock, $stock);

        $model = new shopOrderItemsModel();

        //Вычисляем добавочную стоимость, если стоимость подарка отлична от нуля
        $additional_price = 0;
        foreach ($new_items as $item) {
            $additional_price += $item['price'] * $item['quantity'];
            $model->insert($item);
        }

        if ($additional_price > 0) {
            $order_model->updateById($params['order_id'], array('total' => $order_data['total'] + $additional_price));
        }

        //Пишем в лог заказа
        $view = wa()->getView();
        $view->assign('to_log', $to_log);

        $log_model = new shopOrderLogModel();
        $log_data = array(
            'action_id' => '',
            'contact_id' => null,
            'order_id' => $params['order_id'],
            'before_state_id' => $order_data['state_id'],
            'after_state_id' => $order_data['state_id'],
            'text' => $view->fetch($this->path . '/templates/actions/backend/OrderCreate.html'),
        );
        $log_model->add($log_data);
    }

    public function cartToOrderTransfer($order_items)
    {

        $this->cart_to_order = array();
        $this->product_to_order = array();

        $cart = new shopCart();
        $cart_items = $cart->items();


        //Вызов из плагинов
        $is_plugin = waRequest::param('plugin');

        if (!empty($is_plugin)) {

            foreach ($order_items as $plugin_key => $plugin_item) {

                if ($plugin_item['type'] != 'product') {
                    continue;
                }

                //Устанавливаем выбор на страницах товара
                $this->product_to_order[$plugin_key] = 'p' . $plugin_item['product_id'];
            }
        }


        if (empty($order_items) || empty($cart_items) || (count($order_items) != count($cart_items))) {
            return;
        }

        $i = 0;
        foreach ($order_items as $key => $order_item) {

            if ($order_item['type'] != 'product') {
                continue;
            }

            $j = 0;
            foreach ($cart_items as $cart_key => $cart_item) {

                if ($cart_item['type'] != 'product') {
                    continue;
                }

                if (($i == $j) && ($order_item['product_id'] == $cart_item['product_id'])) {
                    $this->cart_to_order[$order_item['id']] = $cart_item['id'];
                    break;
                }
                $j++;
            }
            $i++;
        }
    }

    /**
     * Получает текст обратной связи, какой товар надо купить, чтобы получить текущий в подарок
     *
     * @param array $rule
     * @return string
     */
    public function getRevertTextFromRule($rule)
    {
        //wa_dump($rule);

        if (empty($rule['group']['items'])) {
            return '';
        }

        $all_for_product = array();
        $all_for_category = array();

        $condition = ($rule['group']['op'] == 'or') ? 'or' : 'and';
        $result = '';

        //Перебираем условия
        foreach ($rule['group']['items'] as $group) {

            //Если значение не задано
            if (empty($group['op_type'])) {
                continue;
            }

            $group_type = $group['op_type'];
            $group_operation = $group['operation'];

            //Когда условие - И
            if ($condition == 'and') {
                //Пока можем определять только по товару и категории
                if (($group_type != 'product') && ($group_type != 'category')) {
                    return '';
                }
            }

            if ($group_type == 'product') {

                //Только выбранные продукты
                if ($group_operation != '=') {
                    continue;
                }

                //Не выбран продукт
                if (empty($group['product'])) {
                    continue;
                }

                $product_id = $group['product'];
                $product = new shopProduct($product_id);

                if (empty($product['id']) || empty($product['status'])) {
                    continue;
                }

                if (self::getIgnoreStockCount()) {
                    $all_for_product[] = $product;
                } else {
                    //NULL когда количество товара не указано и он бесконечность
                    if (($product['count'] > 0) || (is_null($product['count']))) {
                        $all_for_product[] = $product;
                    }
                }
            } elseif ($group_type == 'category') {

                if ($group_operation != '=') {
                    continue;
                }

                //Не выбрана категория
                if (empty($group['category'])) {
                    continue;
                }

                $category_id = $group['category'];
                $category_model = new shopCategoryModel();
                $category = $category_model->getById($category_id);

                if (empty($category)) {
                    continue;
                }

                $category['with_childs'] = empty($group['with_childs']) ? 0 : 1;

                $all_for_category[] = $category;
            }
        }

        $rule['condition'] = $condition;

        $variables = array(
            'rule' => $rule,
            'need_products' => $all_for_product,
            'need_categories' => $all_for_category
        );

        return $this->parseTemplate('feedback.html', $variables);
    }
}
