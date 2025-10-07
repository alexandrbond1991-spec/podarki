<?php

/**
 * расширение: работа в публичной части
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitFrontend
{
    /**
     * @var null shopProductsCollection
     */
    public static $_cache_prepared_badges = null;

    /**
     * Хук => Подключаем css js в зависимости от витрины
     */
    public function frontendHead()
    {
        //Подключаем CSS
        $main_css = $this->getTemplatePath('frontend.css');
        $theme_css = $this->getTemplatePath('theme.css');
        $custom_css = $this->getTemplatePath('vars_css.html');

        $custom_css = $this->getCustomCss($custom_css);

        //Минимизированная версия
        $main_min_css = str_replace('frontend.css', 'frontend.min.css', $main_css);
        if (is_readable($main_min_css)) {
            $main_css = $main_min_css;
        }

        $css_dir = wa()->getDataPath('plugins/' . $this->id . '/css/', true, 'shop');

        $css_data = '';

        if (!empty($main_css)) {
            $css_data .= file_get_contents($main_css);
        }

        if (!empty($theme_css)) {
            $css_data .= file_get_contents($theme_css);
        }
        if (!empty($custom_css)) {
            $css_data .= file_get_contents($custom_css);
        }
        $css_filename = 'all_' . md5($css_data) . '.css';

        if (!file_exists($css_dir . $css_filename)) {
            file_put_contents($css_dir . $css_filename, $css_data);
        }

        $url = substr(wa()->getDataUrl('plugins/' . $this->id . '/css/', true, 'shop'), 1) . $css_filename;
        wa()->getResponse()->addCss($url);


        //Подключаем JS
        $theme_js = $this->getTemplatePath('theme.js');
        $main_js = $this->getTemplatePath('frontend.js');

        //Проверяем min версии
        $theme_min_js = str_replace('theme.js', 'theme.min.js', $theme_js);
        $main_min_js = str_replace('frontend.js', 'frontend.min.js',$main_js);

        //Есть дополнительный JS - объединяем
        if (!empty($theme_js)) {

            if (is_readable($theme_min_js)) {
                $theme_js = $theme_min_js;
            }

            if (is_readable($main_min_js)) {
                $main_js = $main_min_js;
            }
            
            $result_js = file_get_contents($main_js) . file_get_contents($theme_js);

            $dir = wa()->getDataPath('plugins/' . $this->id . '/js/', true, 'shop');

            $filename = md5($result_js) . '.js';

            if (!file_exists($dir . $filename)) {
                file_put_contents($dir . $filename, $result_js);
            }

            $url = substr(wa()->getDataUrl('plugins/' . $this->id . '/js/', true, 'shop'), 1) . $filename;

            wa()->getResponse()->addJs($url);
        } else {
            $main_js = $this->getTemplateUrl('frontend.js');
            $version = $this->getHumanVersion();
            if (is_readable($main_min_js)) {
                $main_js = str_replace('frontend.js', 'frontend.min.js?v=' . $version, $main_js);
            } else {
                $main_js = str_replace('frontend.js', 'frontend.js?v=' . $version, $main_js);
            }

            wa()->getResponse()->addJs(substr($main_js, 1));
        }

        $base_url = htmlspecialchars(wa('shop')->getRouteUrl('shop/frontend'));

        return "<script>if (typeof shop_addgifts__frontend == 'undefined') {
            document.addEventListener('DOMContentLoaded', function () {
                shop_addgifts__frontend.base_url = '$base_url';
            })
        } else {
            shop_addgifts__frontend.base_url = '$base_url';
        } 
        </script>";
    }


    /**
     * Хук => Вывод подарка к товару
     * @param $product
     * @return array
     */
    public function frontendProduct($product)
    {
        $result = self::inProduct($product, false);

        if ($result !== false) {

            $settings = $this->getStorefrontSettings();
            return array($settings['hook'] => $result);
        }
    }


    /**
     * Хук => Выводим общий список подарков (если включено в настройках)
     * @return string
     */
    public function frontendCart()
    {
        return self::inCartFull();
    }

    /**
     * Хук => Выводим общий список подарков на странице подтверждения заказа (если включено в настройках)
     * @param $params - шаг оформлния заказа
     * @return string
     */
    public function frontendCheckout($params)
    {
        if ($params['step'] == 'confirmation') {
            return self::inCartFull();
        }
    }

    /**
     * Хук => Переменные в новом оформление заказа
     * @param $params
     * @return void|null|array
     */
    public function frontendOrderCartVars(&$params)
    {

        //Плагин установлен и включен
        if (!$this->isEnable()) {
            return;
        }

        //Настройки витрины
        $settings = $this->getStorefrontSettings();

        //Плагин включен на данной витрине
        if (empty($settings['enable'])) {
            return;
        }

        $items = $params['cart']['items'];
        $sum_in_cart = $params['cart']['total'];

        $grouped_rules = $this->getCartGifts($items, $sum_in_cart);

        //Перебираем корзину
        foreach ($items as $key => $cart_item) {

            if ($cart_item['type'] != 'product') {
                continue;
            }

            $rules = isset($grouped_rules[$cart_item['id']]) ? $grouped_rules[$cart_item['id']] : array();

            $html = $this->inCartItem($rules, $cart_item['id'], $settings);

            if (!empty($cart_item['cart_custom_html'])) {
                $html = $cart_item['cart_custom_html'] . $html;
            }

            $params['cart']['items'][$key]['cart_custom_html'] = $html;
        }

        //Правила после
        $rules = isset($grouped_rules['after']) ? $grouped_rules['after'] : array();
        $after_items = $this->inCartItem($rules, 'after');

        return array('after_items' => $after_items);
    }

    /**
     * Хук => Добавление в корзину
     * @param $item
     */
    public function cartAdd($item)
    {
        if (empty($item['product_id']) || ($item['type'] != 'product')) {
            return;
        }

        $cookies = waRequest::cookie();

        //Для быстродействия просто транислируем куки на существующие правила
        $rules = shopAddgiftsRulesModel::getRules();
        foreach ($rules as $rule) {

            $need_name = 'addgifts__sku_vid_p' . $item['product_id'] . '_r_' . $rule['id'];

            foreach ($cookies as $name => $cookie) {
                if (substr($name, 0, strlen($need_name)) == $need_name) {

                    //Получаем финальное имя для элемента корзины
                    $final_name = 'addgifts__sku_vid_' . $item['id'] . '_r_' . $rule['id'];
                    $final_name .= substr($name, strlen($need_name));

                    //Если не было уже установлено в корзине
                    if (!isset($cookies[$final_name])) {
                        wa()->getResponse()->setCookie($final_name, $cookie, time() + 30 * 86400, '/');
                    }

                    //Получаем финальное имя для сгруппированного правила
                    $final_name = 'addgifts__sku_vid_after_r_' . $rule['id'];
                   // $final_name .= substr($name, strlen($need_name));

                    //Если не было уже установлено в корзине
                    if (!isset($cookies[$final_name])) {
                        wa()->getResponse()->setCookie($final_name, $cookie, time() + 30 * 86400, '/');
                    }
                }
            }
            //Имя для выбранных подарков

            $need_name = 'addgifts__gift_p_p' . $item['product_id'] . '_r_' . $rule['id'];
            foreach ($cookies as $name => $cookie) {
                if (substr($name, 0, strlen($need_name)) == $need_name) {

                    //Получаем финальное имя для элемента корзины
                    $final_name = 'addgifts__gift_p_' . $item['id'] . '_r_' . $rule['id'];
                    $final_name .= substr($name, strlen($need_name));

                    //Если не было уже установлено в корзине
                    if (!isset($cookies[$final_name])) {
                        wa()->getResponse()->setCookie($final_name, $cookie, time() + 30 * 86400, '/');
                    }

                    //$need_name = 'addgifts__gift_p_' . $item['id'] . '_r_' . $rule['id'];
                    //Получаем финальное имя для сгруппированного правила
                    $final_name = 'addgifts__gift_p_after_r_' . $rule['id'];
                    $final_name .= substr($name, strlen($need_name));
                    wa()->getResponse()->setCookie($final_name, $cookie, time() + 30 * 86400, '/');

                }
            }

        }
    }

    /**
     * Хук => Удаление из корзины
     * @param $item
     */
    public function cartDelete($item)
    {
        if (empty($item['product_id']) || ($item['type'] != 'product')) {
            return;
        }
        $cookies = waRequest::cookie();
        $need_name = 'addgifts__sku_vid_' . $item['id'] . '_r_';

        foreach ($cookies as $name => $cookie) {
            if (substr($name, 0, strlen($need_name)) == $need_name) {
                wa()->getResponse()->setCookie($name, null, -1, '/');
            }
        }

        //Удаление кук выбранного подарка
        $need_name = 'addgifts__gift_p_' . $item['id'] . '_r_';

        foreach ($cookies as $name => $cookie) {
            if (substr($name, 0, strlen($need_name)) == $need_name) {
                wa()->getResponse()->setCookie($name, null, -1, '/');
            }
        }


    }


    public function additionalParseTemplate($view, $theme_id)
    {
        $theme = new waTheme($theme_id);

        $elements = array('el_change_sku.html', 'el_name.html');

        foreach ($elements as $el) {
            $filepath = $theme->getPath() . '/' . $this->id . '__' . $el;

            if (!is_readable($filepath)) {
                $filepath = $this->path . '/templates/actions/frontend/' . $el;
            }

            $view->assign('include__' . str_replace('.html', '', $el), $filepath);
        }
    }

    public static function preparedBadges($products = array())
    {
        $ids = array();

        if (!empty($products)) {
            foreach ($products as $product) {
                $ids[] = $product['id'];
            }
        }

        $collection = new shopProductsCollection("giftrule");
        if (!empty($ids)) {
            $collection->addWhere('id in (' . implode(',', $ids) . ')');
        }

        self::$_cache_prepared_badges = array();

        $products = $collection->getProducts('id', 0, 99999);

        foreach ($products as $product) {
            self::$_cache_prepared_badges[$product['id']] = $product['id'];
        }
    }

    public static function badgeHtml($product, $route = false)
    {

        if (is_string($product)) {
            return shopHelper::getBadgeHtml($product);
        }

        $default = shopHelper::getBadgeHtml($product['badge']);

        /** @var shopAddgiftsPlugin $instance */
        $instance = self::getInstance();

        if (!$instance->isEnable()) {
            return $default;
        }

        $settings = $instance->getStorefrontSettings($route);
        if (empty($settings['enable'])) {
            return $default;
        }
// Закоментировано для последющего обновления с новым функционалом наклеек
//        //Предварительная обработка
//        if (is_null(self::$_cache_prepared_badges)) {
//            self::preparedBadges();
//        }
//
//        if (!isset(self::$_cache_prepared_badges[$product['id']])) {
//            return $default;
//        }

        $rules = $instance->getProductGifts($product);
        if (empty($rules)) {
            return $default;
        }

        $count_gifts = 0;
        foreach ($rules as $rule) {
            foreach ($rule['gifts'] as $gift) {
                $count_gifts += $gift['count'];
            }
        }


        $variables = array(
            'rules' => $rules,
            'count_gifts' => $count_gifts,
            'default_badge' => (!empty($settings['many_badges'])) ? $default : ''
        );

        return $instance->parseTemplate('badge.html', $variables, $settings);
    }

    public static function getRules($product, $route = false)
    {

        /** @var shopAddgiftsPlugin $instance */
        $instance = self::getInstance();

        if (!$instance->isEnable()) {
            return false;
        }

        $settings = $instance->getStorefrontSettings($route);
        if (empty($settings['enable'])) {
            return false;
        }

        $rules = $instance->getProductGifts($product);

        if (empty($rules)) {
            return false;
        }

        return $rules;
    }

    /**
     * @param $product
     * @param string|bool $route
     * @return array|bool
     * @deprecated используйте getRules
     */
    public static function getGifts($product, $route = false)
    {
        return self::getRules($product, $route);
    }
}