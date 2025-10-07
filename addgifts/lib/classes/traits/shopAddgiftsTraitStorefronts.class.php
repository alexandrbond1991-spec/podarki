<?php

/**
 * расширение: Мультивитринность
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitStorefronts
{

    public $_storefront_settings_cache = array();

    public static $_cache_get_storefront_settings = array();

    public function getCustomCss($template)
    {
        $storefront = $this->detectStorefront();

        $dir = wa()->getDataPath('plugins/' . $this->id . '/css/', true, 'shop');

        $last_changes = $this->getSettings('last_changes');

        $filename = 'vars_' . md5($template . $storefront . $last_changes) . '.css';

        if (!file_exists($dir . $filename)) {
            file_put_contents($dir . $filename, $this->generateCustomCss($template, $storefront));
        }

        return $dir . $filename;
    }

    public function generateCustomCss($template, $storefront)
    {
        $result = '';

        if (empty($template)) {
            return $result;
        }

        try {

            $default = include $this->path . '/lib/config/storefront_default.php';

            $view = wa()->getView();
            $view->assign('_values', $this->getStorefrontSettings($storefront));
            $view->assign('_default', $default);

            $result = $view->fetch($template);
        } catch (Exception $e) {
            waLog::log('Ошибка компиляции шаблона ' . $template . ': ' . $e->getMessage(), 'addgifts.log');
        }

        return $result;
    }

    /**
     * Сохранениек настроек для витрин
     *
     * @param $storefronts
     */
    public function saveStorefronts($storefronts)
    {

        $model = new shopAddgiftsStorefrontsModel();
        $all_storefronts = $model->getAll('storefront');

        foreach ($storefronts as $key => $value) {

            //$this->generateCustomCss($key, $value);

            $data = array('value' => json_encode($value));

            if (isset($all_storefronts[$key])) {
                $model->updateById($all_storefronts[$key]['id'], $data);
            } else {
                $data['storefront'] = $key;
                $model->insert($data);
            }
        }
    }

    /**
     * Получение настроек для витрин
     *
     * @return array
     */
    public function getStorefronts()
    {
        $result = array();

        $model = new shopAddgiftsStorefrontsModel();
        $storefronts = $model->getAll('storefront');
        foreach ($storefronts as $key => $storefront) {
            $result[$key] = json_decode($storefront['value'], true);
        }

        return $result;
    }

    /**
     * Получение списка витрин для приложения Магазин
     *
     * @return array
     */
    public function getRoutes()
    {
        //Витрины
        wa('site');
        $domains_model = new siteDomainModel();
        $domains = $domains_model->getAll();

        $routing = wa()->getRouting();
        $result = array();

        foreach ($domains as $key => $domain) {
            $routings = $routing->getByApp('shop', $domain['name']);

            foreach ($routings as $r_key => $route) {
                $routings[$r_key]['value'] = $domain['name'] . '/' . $route['url'];
            }
            if (!empty($routings)) {
                $domain['routing'] = $routings;
                $result[] = $domain;
            }
        }

        return $result;
    }


    public function detectStorefront()
    {
        $routing = wa()->getRouting();
        $domain = $routing->getDomain(null, true);
        $a_route = $routing->getRoute();

        return $domain . '/' . $a_route['url'];
    }

    public function getStorefrontSettings($storefront = false)
    {

        $_cache_key = $storefront;

        if (isset(self::$_cache_get_storefront_settings[$_cache_key])) {
            return self::$_cache_get_storefront_settings[$_cache_key];
        }

        if ($storefront === false) {
            $storefront = $this->detectStorefront();
        }

        $storefronts = $this->getStorefronts();

        $result = (isset($storefronts[$storefront])) ? $storefronts[$storefront] : $storefronts['0'];
        $result['route'] = $storefront;

        self::$_cache_get_storefront_settings[$_cache_key] = $result;

        return $result;
    }

    public function getStorefrontSetting($name, $storefront = false)
    {
        $settings = $this->getStorefrontSettings($storefront);
        return $settings[$name];
    }

    public function createDefaultStorefrontVariables()
    {
        $default = array('0' => include $this->path . '/lib/config/storefront_default.php');
        $default[0]['gift_image'] = $this->getPluginStaticUrl() . 'img/gift.png';
        $default[0]['cart_gift_image'] = $this->getPluginStaticUrl() . 'img/gift.png';
        $this->saveStorefronts($default);
    }
}