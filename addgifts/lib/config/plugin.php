<?php
/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
return array(
    'name' => 'Подарки',
    'description' => 'Дает возможность прикреплять к товару подарок',
    'vendor' => '962376',
    'version' => '2.5.2',
    'img' => 'img/plugin_icon.png',

    'frontend' => true,
    'custom_settings' => true,
    'handlers' => array(

        'promo_rules' => 'promoRules',

        'backend_product' => 'backendProduct',
        'backend_products' => 'backendProducts',
        'backend_orders' => 'backendOrders',

        'frontend_head' => 'frontendHead',
        'frontend_cart' => 'frontendCart',
        'frontend_checkout' => 'frontendCheckout',
        'frontend_product' => 'frontendProduct',

        'cart_add' => 'cartAdd',
        'cart_delete' => 'cartDelete',
        'frontend_order_cart_vars' => 'frontendOrderCartVars',
        'order_action.create' => 'orderActionCreate',

        'products_collection' => 'productsCollection',
        'rights.config' => 'rightsConfig',
    ),
);