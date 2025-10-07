<?php
return array(
    array(
        'name' => 'Шаблоны',
        'templates' => array(
            array(
                'filename' => 'badge.html',
                'name' => 'Наклейка',
                'info' => 'Используется для вывода наклеек',
                'variables' => array(
                    '$count_gifts' => 'кол-во подарков',
                    '$default_badge' => 'наклейка по умолчанию (передается если стоит галочка использовать множественные наклейки)',
                    '$rules' => 'примененные правила с подарками'
                )
            ),
            array(
                'filename' => 'product.html',
                'name' => 'На странице товара',
                'info' => 'Для вывода списка подарков на странице товара',
                'variables' => array(
                    '$rules' => 'примененные правила с подарками'
                )
            ),
            array(
                'filename' => 'revert.html',
                'name' => 'На странице подарка',
                'info' => 'Выводит обратную связь на странице подарка',
                'variables' => array(
                    '$rules' => 'примененные правила с подарками'
                )
            ),
            array(
                'filename' => 'feedback.html',
                'name' => 'Текст обратной связи',
                'info' => '',
                'variables' => array(
                    '$rule' => 'активное правило',
                    '$need_products' => 'продукты которые нужно купить',
                    '$need_categories' => 'категории товары из которых нужно купить'
                )
            ),

            array(
                'filename' => 'cart_item.html',
                'name' => 'В корзине/Оформление',
                'info' => 'Выводит подарки в оформление заказа,корзине',
                'variables' => array(
                    '$rules' => 'примененные правила с подарками',
                    '$vid' => 'идентификатор объекта (id элемента корзины или after)'
                )
            ),

            array(
                'filename' => 'el_name.html',
                'name' => '-- Название и кол-во',
                'info' => 'Дочерний элемент. Вывод название и кол-во подарка',
                'variables' => array(
                    '$rule' => 'активное правило',
                    '$gift' => 'инфоррмация о подарке (внимание! данные о товаре находятся в $gift.product)',
                    '$price_comment' => 'информация о цене подарка (определяется в родительских шаблонах)'
                )
            ),
            array(
                'filename' => 'el_change_sku.html',
                'name' => '-- Выбор артикула',
                'info' => 'Дочерний элемент. Выбор артикула у мултиартикульных товаров',
                'variables' => array(
                    '$rule' => 'активное правило',
                    '$gift' => 'инфоррмация о подарке (внимание! данные о товаре находятся в $gift.product)',
                )
            ),

        )
    ), array(
        'name' => 'CSS/JS',
        'templates' => array(
            array(
                'filename' => 'frontend.css',
                'name' => 'Основной CSS',
                'icon' => 'script-css',
                'default' => '/css/frontend.css',
                'info' => 'Основной CSS файл',
            ),
            array(
                'filename' => 'theme.css',
                'name' => 'CSS для темы',
                'default' => '/css/themes/[+theme_id+].css',
                'icon' => 'script-css',
                'info' => 'Набор CSS правил для конкретной темы дизайна',
            ),
            array(
                'filename' => 'vars_css.html',
                'name' => 'CSS для переменных',
                'info' => 'Генерация CSS на основание переменные в настройках витрины',
                'variables' => array(
                    '$_values' => 'значения активной витрины',
                    '$_default' => 'значения по умолчниаю',
                ),
                'hide_default_variables' => '1'
            ),
            array(
                'filename' => 'frontend.js',
                'name' => 'Основной JS',
                'icon' => 'script-js',
                'default' => '/js/frontend.js',
                'info' => 'Основной JavaScript файл (не рекомендуется изменять)',
            ),
            array(
                'filename' => 'theme.js',
                'name' => 'Дополнительный JS',
                'icon' => 'script-js',
                'default' => '/js/themes/[+theme_id+].js',
                'info' => 'Можете использовать для внедрения своего JavaScript кода',
            ),
        )
    )
);
