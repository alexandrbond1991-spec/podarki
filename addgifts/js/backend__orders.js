function ShopAddgiftsBackendOrders() {

    var that = this;

    that.gifts = [];

    that.addGift = function (gift) {
        //Shop-script original code
        var url = '?module=orders&action=getProduct&product_id=' + gift.product_id + '&order_id=' + $.order_edit.id + '&currency=' + $.order_edit.options.currency;
        $.getJSON(url, function (r) {
            var table = $('#order-items');
            var index = parseInt(table.find('.s-order-item:last').attr('data-index'), 10) + 1 || 0;
            var product = r.data.product;

            //Изменяем название и цену
            product.name = gift.name;
            product.price = gift.price;

            //Выбранный SKU в подарке
            if (gift.sku_id && product.skus[gift.sku_id]) {
                product.skus[gift.sku_id].checked = true;
            }

            if ($('#order-currency').length && !$('#order-currency').attr('disabled')) {
                $('<input type="hidden" name="currency">').val($('#order-currency').val()).insertAfter($('#order-currency'));
                $('#order-currency').attr('disabled', 'disabled');
            }

            var add_row = $('#s-orders-add-row');
            add_row.before(tmpl('template-order', {
                data: r.data, options: {
                    index: index,
                    currency: $.order_edit.options.currency,
                    stocks: $.order_edit.stocks,
                    price_edit: 1
                }
            }));

            var item = add_row.prev();

            //Обновляем внешний вид, цену и кол-во
            item.addClass('js-addgifts__added').css('borderLeft', 'solid 3px green');
            item.find('.s-orders-product-price input').val(gift.price);
            item.find('.s-orders-quantity').val(gift.quantity);

            $('#s-order-comment-edit').show();

        });
    };

    $(document).on('click', '.js-addgifts__add_button', function (e) {
        e.preventDefault();

        var $button = $(this);
        $button.attr('disable', 'disabled');

        //Помечаем товары как добавленные с подарками
        $('[name*="product[add]"]').each(function () {

            var $input = $(this);
            var $parent = $input.closest('.s-order-item');

            $parent.addClass('js-addgifts__added');
        });

        $('.js-addgifts__order_item').remove();
        $('.js-addgifts__order_after').closest('tr').remove();


        for (var i in that.gifts) {
            that.addGift(that.gifts[i]);
        }

        $button.hide();
    });

    //Собираем данные чтобы расчитать подарки
    $(document).on('order_total_updated', function () {

        var new_items = [];

        //Работаем только с новыми добавленными товарами
        $('[name*="product[add]"]').each(function () {

            var $input = $(this);
            var $parent = $input.closest('.s-order-item');

            if (!$parent.hasClass('js-addgifts__added')) {

                var item = {
                    'id': $parent.data('index'),
                    'product_id': $parent.data('product-id'),
                    'quantity': $parent.find('.s-orders-quantity').val(),
                    'price': $parent.find('.js-order-edit-item-price').val(),
                    'type': 'product'
                };

                new_items.push(item);
            }
        });


        if (new_items.length) {

            var $add_button = $('.js-addgifts__add_button');

            if (!$add_button.length) {
                $('<span class="js-addgifts__check_gifts" style="display: none">проверяем подарки ... </span><button type="button" style="display: none;margin-right: 15px" class="js-addgifts__add_button button green">Добавить подарки</button>').insertBefore('#order-items .save .button');
                $add_button = $('.js-addgifts__add_button');
            }

            var $loading = $('.js-addgifts__check_gifts');
            $loading.show();

            $add_button.hide().attr('disabled', 'disabled');

            $('.shop_addgifts--order-cart-item').addClass('shop_addgifts--loading');

            $.post('?plugin=addgifts&action=orderedit', {items: new_items}, function (response) {

                if (response.data != 'none') {
                    for (var i in response.data.items) {

                        var item = response.data.items[i];

                        var $item = $('.s-order-item[data-index="' + item.id + '"]');

                        var $insert = $item.find('.js-addgifts__order_item');

                        if (!$insert.length) {
                            $insert = $('<div></div>').addClass('js-addgifts__order_item');
                            $item.find('td').first().next().append($insert)
                        }
                        $insert.html(item.html);

                    }

                    var $after = $('.js-addgifts__order_after');

                    if (!$after.length) {
                        $('<tr class="white"><td colspan="10"><div class="js-addgifts__order_after"</td></td></tr>').insertAfter($('#s-orders-add-row'));
                        $after = $('.js-addgifts__order_after');
                    }

                    $after.html(response.data.after_items ? response.data.after_items : '');

                    if (response.data.have_gifts) {
                        $add_button.show();
                        $add_button.removeAttr('disabled');
                    }
                    that.gifts = response.data.gifts;

                } else {
                    that.gifts = [];
                }

                $loading.hide();
            })
        }

    });
}

//Глобальная переменная для доступа из backend
var shop_addgifts__backend_orders = new ShopAddgiftsBackendOrders();