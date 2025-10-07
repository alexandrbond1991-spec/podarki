<?php

/**
 * расширение: работа в админке
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitBackend
{

    /**
     * Хук => Подключает CSS JS, создает ссылку на Подарки в разделе Товары
     * @return array
     */
    public function backendProducts()
    {
        if ($this->getRights('set') && $this->getSettings('enable')) {

            $version = $this->getHumanVersion();

            //CSS для Backend
            $this->addCss('css/backend.min.css?v=' . $version);

            //JS для Backend
            $this->addJs('js/backend.min.js?v=' . $version);

            $view = wa()->getView();
            return array(
                'sidebar_top_li' => $view->fetch($this->path . '/templates/actions/backend/BackendProducts.html')
            );
        }
    }


    /**
     * Выводим подарки при просмотре товара
     * @param $product
     * @return array
     */
    public function backendProduct($product)
    {
        $view = wa()->getView();
        $view->assign('rules', $this->getProductGifts($product));
        return array(
            'toolbar_section' => $view->fetch($this->path . '/templates/actions/backend/BackendProduct.html'),
        );
    }

    /**
     * Хук => Подключение скриптов в бакэнд
     */
    public function backendOrders()
    {
        $version = $this->getHumanVersion();

        //JS для Backend
        $this->addJs('js/backend__orders.min.js?v=' . $version);
        $this->addCSS('css/frontend.min.css?v=' . $version);
        $this->addCSS('css/backend.min.css?v=' . $version);
    }


    public function inBackendOrderItem($rules, $vid)
    {

        $view = wa()->getView();

        $view->assign('rules', $rules);
        $view->assign('vid', $vid);

        $view->assign('settings', $this->getStorefrontSettings());
        $view->assign('common_settings', $this->getSettings());

        $view->assign('current_theme', waRequest::getTheme());
        $view->assign('base_url', wa('shop')->getRouteUrl('shop/frontend'));

        $file_template = $this->path . '/templates/actions/backend/OrderItem.html';
        return $view->fetch($file_template);
    }

    /**
     * Рекурсивная функция получения продуктав в условиях правила
     *
     * @param $group
     * @param $result
     * @return mixed
     */
    public function getProductsInGroup($group, $result)
    {
        if (!empty($group['items'])) {
            foreach ($group['items'] as $item) {

                if ($item['type'] == 'group') {
                    $result = $this->getProductsInGroup($item, $result);
                } elseif ((!empty($item['op_type'])) && ($item['op_type'] == 'product') && (!empty($item['product']))) {
                    $result[$item['product']] = $item['product'];
                }
            }
        }
        return $result;
    }

    /**
     * Получить все продукты встречающиеся в правилах - необходимо для оптимизации
     * @return array
     */
    public function getProductsInRules()
    {
        $model = new shopAddgiftsRulesModel();
        $rules = $model->order('sort asc')->fetchAll();

        $result = array();

        $products = array();


        foreach ($rules as $key => $rule) {
            $decoded_rule = json_decode($rule['rule'], true);

            if (!empty($decoded_rule['gifts'])) {
                foreach ($decoded_rule['gifts'] as $gift) {
                    if (!empty($gift['product'])) {
                        $products[$gift['product']] = $gift['product'];
                    }
                }
            }

            if (!empty($decoded_rule['group'])) {
                $products = $this->getProductsInGroup($decoded_rule['group'], $products);
            }
        }

        if (!empty($products)) {
            $result = $this->getProducts($products);
        }
        return $result;
    }

}