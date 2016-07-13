$(function() {
    bind_location_submit_form();
    bind_callcenter_place_select();
    bind_table_filtering();
    bind_reset_cart();
    bind_order_submit_form();
    bind_back_to_dishes();
    focus_table_row();
});

$(window).load(function() {
    bind_location_select();
    bind_address_search_by_phone.init();
    focus_table_row();
});

var focus_table_row = function() {
    var focus_button = $('.focus_button');

    focus_button.on('focus', function() {
        $(this).parent().parent().addClass('highlightedRow');
    });

    focus_button.on('blur', function() {
        $(this).parent().parent().removeClass('highlightedRow');
    });
};

bind_location_select = function() {
    var focus_street;

    focus_street = function() { $(LocationInputs.street).focus().focus(); };

    $(LocationInputs.city).change(function() {
        focus_street();
    });

    focus_street();
};

bind_select2 = function() {
    var elements, options;

    elements = $('.select2');
    options = {
        allowClear: true,
        width: 200,
        matcher: function(term, text) { return fuzzy_search(term, text); }
    };

    elements.select2(options);
};

bind_callcenter_place_select = function() {
    var change_callback,
        place_url;

    // our subjects
    place_url = '#place_url';

    // our callback
    change_callback = function() {
        if ($(this).val().length == 0) return;

        var options, menu_url, route_name, route_options, loader_options;

        // load data from this URL
        route_name = 'food_callcenter_load_menu';
        route_options = {'placeId': $(PlaceSelect.selector).val()};
        menu_url = Routing.generate(route_name, route_options);

        // ajax options
        options = {
            type: 'GET',
            url: menu_url,
            success: function(response) {
                var selected_place_id;

                selected_place_id = $(PlaceSelect.selector).val();

                DishesContent.setHtml(response).show("fast", function() {
                    $('#dishes_filter').focus().focus();
                });
                ResetCartButton.show();
                NoDishesContent.hide();
                SuccessPanel.hide();
                MainPanel.show();
                CartPanel.show();

                // bind order button in side block
                bind_order_button(selected_place_id);

                // don't forget to turn loader off
                $(PlaceSelect.selector).isLoading('hide');

                // init focus color
                focus_table_row();

                // set DOM variables
                $(PlaceId.selector).attr('data', selected_place_id);
                $(place_url).attr('data', Routing.generate('food_cart', { placeId: selected_place_id, takeAway: 1, _locale: 'lt' }));

                var data = $($.parseHTML(response));

                $('.restaurant_link').attr('href', data.find('#restaurant_link').attr('data'))
                    .attr('title', data.find('#restaurant_image_alt').attr('data'));
                $('.restaurant_image').attr('src', data.find('#restaurant_image').attr('data'))
                    .attr('alt', data.find('#restaurant_image_alt').attr('data'));

                // focus our glorious filter
                $('#dishes_filter').focus().focus();
            }
        };

        // turn loader on
        loader_options = {class: 'fa fa-cog fa-spin', text: '  '};
        $(PlaceSelect.selector).isLoading(loader_options);

        // do ajax
        $.ajax(options);

        return false;
    };

    // our event
    $(PlaceSelect.selector).change(change_callback);
};

bind_table_filtering = function() {
    var subject, table_row, callback;

    subject = '#dishes_filter';
    table_row = '.searchable';

    callback = function(e) {
        var val, filter_callback;

        // 27 == escape key
        if (e.keyCode == 27) {
            val = '';
            $(subject).val('');
        } else {
            val = $(this).val();
        }

        filter_callback = function() {
            return fuzzy_search(val, $(this).text());
        }

        $(table_row).hide();
        $(table_row).filter(filter_callback).show();

    };

    $('body').on('keyup', subject, callback);
};

translit_letters = function(value) {
    var result, replacements;

    result = value.toLowerCase();
    replacements = {'ą': 'a',
                    'č': 'c',
                    'ę': 'e',
                    'ė': 'e',
                    'į': 'i',
                    'š': 's',
                    'ų': 'u',
                    'ū': 'u',
                    'ž': 'z'};

    $.each(replacements, function(key, value) {
        result = result.replace(key, value);
    });

    return result;
};

manually_select_place = function(placeId) {
    $(PlaceSelect.selector).select2('val', placeId).trigger('change');
    $('#dishes_filter').focus().focus().val('').trigger('keyup');
};

bind_reset_cart = function() {
    var reset_url,
        options,
        loader_options;

    // our subjects
    reset_url = Routing.generate('food_callcenter_reset');

    $(ResetCartButton.selector).click(function() {
        var $this = $(this);

        // turn loader on
        loader_options = {class: 'fa fa-cog fa-spin', text: '  '};
        $this.isLoading(loader_options);

        options = {
            type: 'GET',
            url: reset_url,
            success: function(response) {
                $this.hide();
                CheckoutContent.hide();
                DishesContent.setHtml('');
                NoDishesContent.show();
                SuccessPanel.hide();
                PlaceSelectPanel.hide();
                CartMenuContent.setHtml('');
                $(PlaceSelect.selector).select2('val', '');
                $this.isLoading('hide');
                MainPanel.hide();
                CartPanel.hide();
                BackToDishesButton.hide();
                // LocationInputs.clear();
                LocationPanel.hide();
                DeliveryImpossible.hide();
                LocationError.hide();
                PlaceId.clear();
            }
        };

        // do ajax
        $.ajax(options);

        return false;
    });
};

bind_order_button = function(placeId) {
    var subject;

    subject = '.order-button';

    $('body').on('click', subject, get_checkout_form_callback);
};

get_checkout_form_callback = function() {
    var checkout_url, options;
    checkout_url = Routing.generate('food_callcenter_checkout');

    options = {
        type: 'GET',
        url: checkout_url,
        success: checkout_callback
    };

    $.ajax(options);

    return false;
};

bind_location_submit_form = function() {
    var subject,
        submit_button,
        success_callback,
        callback;

    subject = '#location_form';
    submit_button = '#location_submit_button';

    success_callback = function(response) {
        if (response.data.success == 1) {
            update_place_select();
            SuccessPanel.hide();

            $.ajax({
                url: Routing.generate('food_callcenter_get_location'),
                dataType: 'json',
                success: function(response) {
                    $('#location_content').html(response.address);
                    LocationPanel.show();
                }
            })
        } else {
            LocationError.setHtml(response.data.message).show();
            PlaceSelectPanel.hide();
            MainPanel.hide();
            CartPanel.hide();
            LocationPanel.hide();
        }

        $(submit_button).isLoading('hide');
    };

    callback = function() {
        var options, loader_options;

        LocationError.hide();

        // turn loader on
        loader_options = {class: 'fa fa-cog fa-spin', text: '  '};
        $(submit_button).isLoading(loader_options);

        options = {
            url: Routing.generate('food_ajax', {action: 'find-address',
                                                _locale: 'lt'}),
            type: 'GET',
            data: {city: $(LocationInputs.city).val(),
                   address: $(LocationInputs.street).val() + ' ' + $(LocationInputs.house).val()},
            success: success_callback
        };

        $.ajax(options);

        return false;
    };

    $(subject).submit(callback);
};

bind_order_submit_form = function() {
    var subject,
        reset_cart_button,
        reset_url,
        callback;

    subject = '.delivery-info-form[method="post"]';
    reset_cart_button = '#reset_cart';
    reset_url = Routing.generate('food_callcenter_reset');

    callback = function() {
        var options, success_callback, takeAway;

        takeAway = 0;

        if ($('input[name="delivery-type"]:checked').val() == 'pickup') {
            takeAway = 1;
        }

        success_callback = function(response) {
            var success = false;

            if ($(response).find('.alert-warning').length) {
                response = $(".alert-warning", response);
                success = false;
            }

            if ($(response).find('.cupon-page').length) {
                response = $(".cupon-page", response);
                success = true;
            }

            if (success) {
                CheckoutContent.setHtml(response);
            } else {
                CheckoutContent.removeAlert();
                CheckoutContent.alert(response);
            }
            $('html, body').animate({scrollTop: $('#main_panel').offset().top});

            // success
            if (success == true) {
                options = {
                    type: 'GET',
                    url: reset_url,
                    success: function(response) {
                        CheckoutContent.hide();
                        CartPanel.hide();
                        DishesContent.setHtml('');
                        MainPanel.hide();
                        PlaceSelectPanel.hide();
                        BackToDishesButton.hide();
                        CartMenuContent.setHtml('');
                        $(PlaceSelect.selector).select2('val', '');
                        $(reset_cart_button).hide();
                        SuccessPanel.show();
                        PlaceSelectPanel.hide();
                        LocationInputs.clear();
                    }
                };

                // do ajax
                $.ajax(options);
            } else {
                checkout_callback();
                bind_custom_select();
            }
        };

        options = {
            url: Routing.generate('food_cart', {placeId: PlaceId.get(), takeAway: takeAway, _locale: 'lt'}),
            type: 'POST',
            data: $(subject).serialize(),
            success: success_callback
        };

        $.ajax(options);

        return false;
    }

    $('body').on('submit', subject, callback);
};

bind_back_to_dishes = function() {
    var callback;

    callback = function() {
        CheckoutContent.setHtml('').hide();
        DishesContent.show();
        BackToDishesButton.hide();
        $('#dishes_filter').val('').focus().focus().trigger('keyup');
        return false;
    };

    $('body').on('click', BackToDishesButton.selector, callback);
};

checkout_callback = function(response) {
    DishesContent.hide();
    CheckoutContent.setHtml(response).show();
    BackToDishesButton.show();
    bind_custom_select();
    $('html, body').animate({scrollTop: $('#main_panel').offset().top});

    bind_coupon_submit_button(PlaceId.get(), $('#check_coupon_url').attr('data'), $('cart_refresh_url').attr('data'));

    // bind_address_change(PlaceId.get(),
    //                     $('#change_translation').attr('data'),
    //                     $('#cancel_translation').attr('data'),
    //                     $('#find_address_and_recount_url').attr('data'),
    //                     $('#take_translation').attr('data'),
    //                     $('#show_places_translation').attr('data'),
    //                     $('#place_url').attr('data'),
    //                     $('#places_url').attr('data'),
    //                     function() {
    //                         var options, success_callback;
    //
    //                         success_callback = function(response) {
    //                             $('.address-change select').val(response.city);
    //                             $('#address').val(response.address_orig);
    //                         };
    //
    //                         options = {
    //                             url: Routing.generate('food_callcenter_retrieve_location'),
    //                             dataType: 'json',
    //                             success: success_callback
    //                         };
    //
    //                         $.ajax(options);
    //                     });

    // do stuff from checkout page
    Cart.placeId = PlaceId.get();
    Cart.locale = 'lt';
    Cart.bindEvents();
};

update_place_select = function() {
    var options;

    DeliveryImpossible.hide();

    options = {
        url: Routing.generate('food_callcenter_get_places_by_location'),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options;

            options = {
                allowClear: true,
                width: 200,
                matcher: function(term, text) { return fuzzy_search(term, text); },
                data: {results: response.places}
            };

            $(PlaceSelect.selector).select2(options);

            if (response.places.length > 0) {
                PlaceSelectPanel.show();

                if (response.place > 0) {
                    PlaceId.set(response.place);

                    manually_select_place(PlaceId.get());
                }
            } else {
                DeliveryImpossible.show();
                PlaceSelectPanel.hide();
                MainPanel.hide();
                CartPanel.hide();
            }
        }
    };

    $.ajax(options);
};

fuzzy_search = function(needle, haystack) {
    fuzzy_list = [];

    $.each(needle.split(''), function(key, value) {
        fuzzy_list.push('.*');
        fuzzy_list.push(value);
    });

    regex = new RegExp(fuzzy_list.join(''), 'img');

    return regex.test(translit_letters(haystack));
}

MainPanel = {
    selector: '#main_panel',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

CartPanel = {
    selector: '#cart_panel',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

BackToDishesButton = {
    selector: '#back_to_dishes',

    show: function() {
        $(this.selector).show().css('display', 'inline-block');
    },

    hide: function() {
        $(this.selector).hide();
    }
};

ResetCartButton = {
    selector: '#reset_cart',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

DishesContent = {
    selector: '#dishes',

    setHtml: function(content) {
        $(this.selector).html(content);
        return this;
    },

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

CheckoutContent = {
    selector: '#checkout',

    setHtml: function(content) {
        $(this.selector).html(content);
        return this;
    },

    alert: function(content) {
        $(this.selector).prepend(content);
        return this;
    },

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    },

    removeAlert: function() {
        $(this.selector).find('.alert').remove();
    }
};

NoDishesContent = {
    selector: '#no_dishes',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

SuccessPanel = {
    selector: '#success_panel',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

CartMenuContent = {
    selector: '#cartmnu',

    setHtml: function(content) {
        $(this.selector).html('');
    }
};

PlaceSelect = {
    selector: '#form_place'
};

PlaceSelectPanel = {
    selector: '#place_select_panel',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

LocationError = {
    selector: '#location_error',

    setHtml: function(content) {
        $(this.selector).html(content);
        return this;
    },

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

LocationInputs = {
    city: '#form_city',
    city_first_option: '#form_city option:first',
    street: '#form_street',
    house: '#form_house',

    clear: function() {
        $(this.city).val($(this.city_first_option).val());
        $(this.street).val('');
        $(this.house).val('');
    }
};

LocationPanel = {
    selector: '#location_panel',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

DeliveryImpossible = {
    selector: '#delivery_impossible',

    show: function() {
        $(this.selector).show();
    },

    hide: function() {
        $(this.selector).hide();
    }
};

PlaceId = {
    selector: '#place_id',

    clear: function() {
        $(this.selector).attr('data', '');
    },

    set: function(id) {
        id = parseInt(id);

        if (id > 0) {
            $(this.selector).attr('data', id);
        }
    },

    get: function() {
        return parseInt($(this.selector).attr('data'));
    }
};


var bind_address_search_by_phone = {
    init: function() {
        $("#top-phone-search").on('submit', function() {
            var check_url, route_name, route_options;
            route_name = 'food_callcenter_get_address_by_phone';
            route_options = {'phone':  $("#top-phone-input").val()};
            check_url = Routing.generate(route_name, route_options);
            $.get(check_url, function(resp) {
                $('<div>' + resp + '</div>').dialog({
                    modal: true,
                    width: 500,
                    open: bind_address_search_by_phone.bindInternals
                });
            });

            return false;
        });
    },
    bindInternals: function() {
        var theDialog = $(this);
        theDialog.find('.set-address').bind('click', function(){
            var theTr = $(this).closest('tr');
            $('#form_city').val(theTr.find('.adrow-city').text());
            $('#form_street').val(theTr.find('.adrow-street').text());
            $('#form_house').val(theTr.find('.adrow-house').text());
            theDialog.dialog('close');
            bind_address_search_by_phone.setUser(theTr.find('.adrow-userId').val());
            bind_address_search_by_phone.setAddress(theTr.find('.adrow-addressId').val());
            $("#location_form").submit();
        });
    },
    setUser: function(userId) {
        var set_url, route_name, route_options;
        route_name = 'food_callcenter_set_user';
        route_options = {'userId':  userId};
        set_url = Routing.generate(route_name, route_options);
        $.ajax(set_url);
    },
    setAddress: function(addressId) {
        var set_url, route_name, route_options;
        route_name = 'food_callcenter_set_address';
        route_options = {'addressId':  addressId};
        set_url = Routing.generate(route_name, route_options);
        $.ajax(set_url);
    }
};