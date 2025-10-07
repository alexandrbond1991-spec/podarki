<?php

/**
 * расширение: Поиск товаров
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
trait shopAddgiftsTraitSearchable
{

    public function _prepareSearchProduct($id)
    {
        $shop_product = new shopProduct($id);

        //Удаленный продукт
        if (empty($shop_product['id'])) {
            return array('id' => "$id", 'name' => 'Товар удален!', 'delete' => '1');
        }

        $image = $shop_product->getImages('32x32', true);

        if (!empty($image)) {
            $image = array_shift($image);
            $image = $image['url_32x32'];
        } else {
            $image = false;
        }


        if ($shop_product['min_price'] != $shop_product['max_price']) {
            $price = shop_currency_html($shop_product['min_price']) . '...' . shop_currency_html($shop_product['max_price']);
        } else {
            $price = shop_currency_html($shop_product['price']);
        }


        $skus = $shop_product['skus'];

        foreach ($skus as $sku_id => $sku) {
            $skus[$sku_id]['price_html'] = shop_currency_html($sku['price'], $shop_product['currency']);
        }

        return array(
            'id' => $shop_product['id'],
            'name' => $shop_product['name'],
            'count' => $shop_product['count'],
            'image' => $image,
            'price_html' => $price,
            'sku_id' => $shop_product['sku_id'],
            'sku_count' => $shop_product['sku_count'],
            'sku_type' => $shop_product['sku_type'],
            'skus' => $skus
        );
    }

    public function getProducts($ids)
    {
        $result = array();
        foreach ($ids as $id) {
            $result[] = $this->_prepareSearchProduct($id);
        }
        return $result;
    }

    public function searchProducts($q = '')
    {
        if (!empty($q)) {
            $auto_complete = new shopBackendAutocompleteController();
            $products = $auto_complete->productsAutocomplete($q, 30);
        } else {
            $collection = new shopProductsCollection();
            $products = $collection->getProducts('*', 0, 10);
        }

        $result = array();

        foreach ($products as $product) {
            $result[] = $this->_prepareSearchProduct($product['id']);
        }

        return $result;
    }
}