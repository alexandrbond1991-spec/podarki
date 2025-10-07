function ShopAddgiftsFrontend() {

    var that = this;

    var prefix = 'addgifts__';
    var js_prefix = '.js-' + prefix;

    that.current_ajax = null;
    that.base_url = '/';

    that.needUpdateCart = function(sum_in_cart) {

        if (!sum_in_cart) {
            sum_in_cart = 0;
        }

        var $cart_items = $('.shop_addgifts--order-cart-item');

        if (!$cart_items.length) {
            return;
        }

        $cart_items.addClass('shop_addgifts--loading');

        if (that.current_ajax) {
            that.current_ajax.abort();
        }

        that.current_ajax = $.getJSON(that.base_url + 'shop_addgifts_fullcart/', { sum_in_cart: sum_in_cart }, function(response) {
            that.current_ajax = null;

            for (var vid in response.data) {
                $('.shop_addgifts--vid-' + vid).replaceWith(response.data[vid]);
            }

            if (typeof response.data['after'] === 'undefined') {
                $('.shop_addgifts--vid-after').replaceWith('<div class="shop_addgifts--vid-after"></div>');
            }
        });

    };

    //Пошаговое оформление заказа
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (xhr.responseJSON) {
            if (xhr.responseJSON.status == 'ok') {
                if (xhr.responseJSON.data && xhr.responseJSON.data.total) {
                    that.needUpdateCart();
                }
            }
        }
    });

    //Оформление заказа в корзине
    $(document).on('wa_order_cart_changed', function(event, response) {
        that.needUpdateCart(response.cart.total);
    });

    //Выбор подарка
    $(document).on('change', js_prefix + 'gift--select_gift', function() {

        var $gift = $(this);
        var $wrapper = $gift.closest('.addgifts');
        var selected = $gift.val();
        var cookie_name = prefix + 'gift_p_' + $gift.data('vid') + '_r_' + $gift.data('rule-id');
        that.cookie(cookie_name, selected);
        
        if ($wrapper.closest('.shop_addgifts--order-cart-item').length) {
            that.needUpdateCart();
        }

    });

    ////Выбор артикула

    //Открываем выбор артикула
    $(document).on('click', js_prefix + 'change_sku', function(e) {
        e.preventDefault();
        $(this).closest(js_prefix + 'gift').find(js_prefix + 'skus').addClass(prefix + 'skus--open');
    });

    //Закрываем выбор артикула
    $(document).on('click', js_prefix + 'skus_close', function(e) {
        e.preventDefault();
        $(this).closest(js_prefix + 'skus').removeClass(prefix + 'skus--open');
    });

    //Выбор артикула
    $(document).on('change', js_prefix + 'select_sku', function() {

        var $sku = $(this);

        var sku_id = $sku.val();
        var cookie_name = prefix + 'sku_vid_' + $sku.data('vid') + '_r_' + $sku.data('rule-id') + '_gi_' + $sku.data('gift-index');
        that.cookie(cookie_name, sku_id);

        var $sku_name = $sku.closest(js_prefix + 'gift').find(js_prefix + 'sku_name');

        $sku_name.html($sku.data('sku-name'));
        $sku_name.data('selected-sku-id', sku_id);

        $sku.closest(js_prefix + 'skus').removeClass(prefix + 'skus--open');

        if ($sku.closest('.shop_addgifts--order-cart-item').length) {
            that.needUpdateCart();
        }

    });


    /**
     * Get the value of a cookie with the given key.
     * @author Klaus Hartl/klaus.hartl@stilbuero.de
     * @param key
     * @param value
     * @param options
     */
    that.cookie = function(key, value, options) {

        // key and value given, set cookie...
        if (arguments.length > 1 && (value === null || typeof value !== "object")) {
            options = jQuery.extend({ path: '/', expires: 30 }, options);

            if (value === null) {
                options.expires = -1;
            }

            if (typeof options.expires === 'number') {
                var days = options.expires,
                    t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            return (document.cookie = [
                encodeURIComponent(key), '=',
                options.raw ? String(value) : encodeURIComponent(String(value)),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path ? '; path=' + options.path : '',
                options.domain ? '; domain=' + options.domain : '',
                options.secure ? '; secure' : ''
            ].join(''));
        }

        // key and possibly options given, get cookie...
        options = value || {};
        var result, decode = options.raw ? function(s) {
            return s;
        } : decodeURIComponent;
        return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
    };


    $(document).ready(function() {
        if ($.tooltipster) {
            $('.js-addgifts__tooltip').tooltipster();
        }
    });
}

//Глобальная переменная для доступа из вне
var shop_addgifts__frontend = new ShopAddgiftsFrontend();