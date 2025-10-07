<?php

/**
 *
 * Получение информации для работы JS
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopAddgiftsPluginBackendInfoController extends waJsonController
{

    public function execute()
    {

        //Категории (пока только статические)
        $category_model = new shopCategoryModel();
        $categories = $category_model->getFullTree('id, name, depth, type', true);

        //Удаляем ненужные ключи
        $categories = array_values($categories);

        //Тип товаров
        $types = new shopTypeModel();

        //Продукты по умолчанию для поиска
        $instance = shopAddgiftsPlugin::getInstance();
        $products = $instance->searchProducts();

        //Валюта
        $currency = wa('shop')->getConfig()->getCurrency(false);
        $currency = waCurrency::getInfo($currency);

        //Списки (пока только статические)
        $set_model = new shopSetModel();
        $sets = $set_model->where('type = '.shopSetModel::TYPE_STATIC)->order('sort asc')->fetchAll();

//        //Характеристики
//        $features_model = new shopFeatureModel();
//        $features = $features_model->getAll();

        $this->response = array(
            'routes' => $instance->getRoutes(),
            'categories' => $categories,
            'types' => $types->getAll(),
            'products' => $products,
            'currency' => $currency,
            'sets' => $sets
        );
    }

}