function ShopAddgiftsBackend(plugin_id) {

    var that = this;

    that.init = function () {

        //Расширение действия sidebar
        $.products.addgiftsAction = function (id_rule) {
            this.load('?plugin=' + plugin_id + '&action=gifts' + (id_rule ? '&id_rule=' + id_rule : ''), function () {
                $("#s-sidebar li.selected").removeClass('selected');
                $("#s-shop_addgifts__sidebar").addClass('selected');
            });
        };

        //Отображение подарков в списках товаров
        $(document).on('product_list_init_view', function () {
            that.addGiftIcon();
        });

        $(document).on('append_product_list', "#product-list", function () {
            that.addGiftIcon();
        });

        that.addGiftIcon();

    };

    that.addGiftIcon = function () {

        var need_ids = [];

        $('#product-list .product').each(function () {
            var $product = $(this);
            if (!$product.hasClass('js-addgifts__iconed')) {
                $product.addClass('js-addgifts__iconed');
                need_ids.push($product.data('product-id'));
            }
        });

        if (need_ids.length) {
            $.get('?plugin=' + plugin_id + '&action=checkGifts', {ids: need_ids}, function (response) {
                if (response.data.with_gifts && response.data.with_gifts.length) {
                    for (var i = 0; i < response.data.with_gifts.length; i++) {
                        $('#product-list .product[data-product-id="' + response.data.with_gifts[i] + '"]').addClass('b-addgifts__icon');
                    }
                }
            });
        }
    };

    //Инициализация при загрузки
    $(document).ready(function () {
        that.init();
    });
}

//Глобальная переменная для доступа из backend
var shop_addgifts__backend = new ShopAddgiftsBackend('addgifts');