<?php

/**
 * Клас расширяющий фозможности стандартного класса
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsEchoPlugin extends shopPlugin
{
    //Поиск продуктов
    use shopAddgiftsTraitSearchable;

    //Мультивитринность
    use shopAddgiftsTraitStorefronts;

    //Шаблоны
    use shopAddgiftsTraitTemplates;

    //Работа в публичной части
    use shopAddgiftsTraitFrontend;

    //Основной функционал (бывший Helper)
    use shopAddgiftsTraitHelper;

    //Работа с коллекциями
    use shopAddgiftsTraitCollection;

    //Работа в административной части
    use shopAddgiftsTraitBackend;

    //Выгрузка пордарков в Яндекс Маркет
    use shopAddgiftsTraitYandex;

    //Обновление
    use shopAddgiftsTraitUpdater;

    public static $instance = null;

    public static $enable_install = null;

    //Сохраняем экземпляр плагина для доступа из статичных методов
    public function __construct($info)
    {
        parent::__construct($info);

        if (is_null(self::$instance)) {
            self::$instance = $this;
        }
    }

    /**
     * @return shopAddgiftsEchoPlugin
     * @throws waException
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = wa('shop')->getPlugin('addgifts');
        }
        return self::$instance;
    }

    public function isEnable()
    {
        return (self::enableInstall($this->id)) && ($this->getSettings('enable'));
    }

    public static function enableInstall($id)
    {
        if (is_null(self::$enable_install)) {
            $plugins = wa('shop')->getConfig()->getPlugins();
            self::$enable_install = !isset($plugins[$id]) ? false : true;
        }

        return self::$enable_install;
    }

    /**
     * Вывод версии плагина (без отладки)
     * @return string
     */
    public function getHumanVersion()
    {
        return $this->info['version'];
    }


    public function toJson($type = '')
    {
        $result = $this->info;

        //Добавляем дополнительные настройки
        $result += include($this->path . '/lib/config/additional.php');

        //Настройки плагина
        $result['settings'] = $this->getSettings();
        $result['static_url'] = $this->getPluginStaticUrl();

        //Данные в зависимости от режима
        if ($type == 'settings') {
            $result['access'] = $this->getRights('settings') ? true : false;

            //$result['themes'] = $this->getThemes();
            $result['templates'] = (object)$this->getTemplates();

            $result['storefronts'] = (object)$this->getStorefronts();
        } elseif ($type == 'backend') {

            $id_rule = waRequest::get('id_rule', 0, waRequest::TYPE_INT);

            if (!empty($id_rule)) {
                $result['id_rule'] = $id_rule;
            }

            $result['access'] = $this->getRights('set') ? true : false;
        } elseif ($type == 'products') {
            $result = $this->getProductsInRules();
        }

        return $result;
    }

    //Убираем лишние из настроек
    public function getSettings($name = null)
    {
        $settings = parent::getSettings($name);

        if (is_null($name)) {
            unset($settings['update_time']);
        }
        return $settings;
    }

    public function normalizeShopProduct($product)
    {
        if (!$product instanceof shopProduct) {
            if (is_array($product) && isset($product['id'])) {
                $product = new shopProduct($product['id']);
            } elseif (wa_is_int($product)) {
                $product = new shopProduct((int)$product);
            } else {
                $product = false;
            }
        }
        return $product;
    }

}