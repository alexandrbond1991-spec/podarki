<?php
/**
 *
 * Получение наличия подарков у товаров
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopAddgiftsPluginBackendCheckGiftsController extends waJsonController
{
    public function execute()
    {
        $ids = waRequest::get('ids', array(), waRequest::TYPE_ARRAY_INT);

        $with_gifts = array();

//        //тип 1 - работает дольше
//        $instance = shopAddgiftsPlugin::getInstance();
//
//        foreach ($ids as $id) {
//
//            $rules = $instance->getProductGifts($id);
//
//            if (!empty($rules)) {
//                $with_gifts[] = $id;
//            }
//        }

        //тип 2 по профилированию работает быстрее
        if (!empty($ids)) {
            $collection = new shopProductsCollection("giftrule");
            $collection->addWhere('id in (' . implode(',', $ids) . ')');
            $products = $collection->getProducts('id', 0, count($ids));

            foreach ($products as $product) {
                $with_gifts[] = $product['id'];
            }
        }

        $this->response['with_gifts'] = $with_gifts;
    }


}