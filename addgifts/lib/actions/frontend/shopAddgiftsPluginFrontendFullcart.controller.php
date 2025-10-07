<?php
/**
 *
 * Обновление корзины по ajax
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopAddgiftsPluginFrontendFullcartController extends waJsonController
{
    public function execute()
    {

        $instance = shopAddgiftsPlugin::getInstance();

        $cart = new shopCart();
        $items = $cart->items();

        $sum_in_cart = waRequest::get('sum_in_cart', 0);
        if (empty($sum_in_cart)) {
            $sum_in_cart = $cart->total();
        }

        $rules = $instance->getCartGifts($items, $sum_in_cart);

        foreach ($rules as $id => $item) {
            $this->response[$id] = $instance->inCartItem($item, $id);
        }

    }

}