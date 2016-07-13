$(document).ready(function() {
    bind_fancybox();
});

bind_fancybox = function() {
    var fancybox,
        fancybox_remove,
        dish_block,
        dish_remove_block,
        options,
        remove_options;

    fancybox = $('.fancy-box');
    fancybox_remove = $('.fancy-box.remove');

    options = {
        padding: 0,
        scrolling: 'visible',
        afterShow: function(e) {
            dish_block = $('#detailed-dish-popup');
            if (dish_block.length > 0) {
                dish_block.find('input').iCheck();
                dish_block.find('.counter').foodCounter(0, 50, 1);
                dish_block.find('.button-submit.add-to-cart:not(.has-bind)').addClass('has-bind').bind('click', function() {
                    addToCart();
                });
                var inputs = $('.dish-ingredients').find('input[type="radio"]');
                var groupNames = [];
                var groupElements = [];
                for (i = 0; i < inputs.length; i++) {
                    var inp = $(inputs[i]);
                    inp.attr('selected', null);
                    inp.closest('.iradio').removeClass('checked');
                    var name = inp.attr('name');
                    name = name.replace("option[", "").replace("]", "");
                    if (groupNames.indexOf(name) == -1) {
                        groupNames.push(name);
                        groupElements.push(inp);
                    }
                }
                for(i = 0; i < groupElements.length; i++) {
                    groupElements[i].attr('checked', 'checked');
                    groupElements[i].closest('.iradio').addClass('checked');
                }
            }
        }
    };

    remove_options = {
        padding : 0,
        scrolling: 'visible',
        afterShow: function(e) {
            dish_remove_block = $('#detailed-dish-popup-remove');
            dish_remove_block.find('.remove-from-cart:not(.has-bind)')
                             .addClass('has-bind')
                             .bind('click', function() { removeFromCart(); });
        }
    };

    fancybox.fancybox(options);
    fancybox_remove.fancybox(remove_options);
}

removeFromCart = function() {
    var remove_from_cart_url,
        dish_remove_block,
        check_block,
        success_callback;

    remove_from_cart_url = Routing.generate('food_cart_action', {action: 'remove', _locale: 'lt'});
    dish_remove_block = $('#detailed-dish-popup-remove');
    check_block = $('.check-block');

    // mask
    dish_remove_block.mask();

    success_callback = function(response) {
        if (typeof(response.block) != 'undefined') {
            check_block.replaceWith(response.block);
        }

        dish_remove_block.unmask();

        $.fancybox.close();
    };

    // ajax
    $.post(remove_from_cart_url,
           dish_remove_block.find('form').serialize(),
           success_callback,
           'json');
}

addToCart = function() {
    var add_to_cart_url,
        dish_block;

    add_to_cart_url = Routing.generate('food_cart_action', {action: 'add', _locale: 'lt'});
    dish_block = $('#detailed-dish-popup');

    if (dish_block.find('.dish-size:checked').length != 0) {
        // mask
        dish_block.mask();

        var place_id = dish_block.find('input[name="place"]').val();

        // TODO needs nice refactoring ;)
        // TODO checkboxas, kad tikrai tikrai perskaiciau

        // Is alcohol and needs confirmation
        var alcoCookie = getCookie('alc8_' + place_id);

        if (dish_block.find('input[name="isAlcohol"]').val() == 1 && alcoCookie == '') {
            $(function() {
                dish_block.find('.alcoholRules div').dialog({
                    resizable: false,
                    height: 600,
                    width: 545,
                    modal: true,
                    stack: true,
                    dialogClass: 'over_fancy_box',
                    buttons: [
                        {
                            id: 'confirm-button',
                            text: '{% trans %}general.alcohol_confirmation.confirm{% endtrans %}',
                            // disabled: true,
                            click: function() {
                                $( this ).dialog( "close" );
                                setCookie('alco_18_' + place_id, 'teip'+place_id, 20);

                                $.post(add_to_cart_url, $('.popup.detailed-dish-popup').serialize(), function(resp) {
                                    if (typeof(resp.block) != "undefined") {
                                        $('.check-block').replaceWith(resp.block);
                                    }
                                    dish_block.unmask();
                                    $.fancybox.close();
                                }, 'json');
                            }
                        },
                        {
                            id: 'cancel-button',
                            text: "{% trans %}general.alcohol_confirmation.exit{% endtrans %}",
                            click: function() {
                                $( this ).dialog( "close" );
                                $.fancybox.close();
                            }
                        }
                    ]
                }).siblings('.ui-dialog-titlebar').remove();
            });
        // Not alcohol - just add to cart
        } else {
            $.post(add_to_cart_url, $('.popup.detailed-dish-popup').serialize(), function(resp) {
                if (typeof(resp.block) != "undefined") {
                    $('.check-block').replaceWith(resp.block);
                }
                dish_block.unmask();
                $.fancybox.close();
            }, 'json');
        }
    }
}
