$(function() {
    bind_site_center();
    bind_tooltip();
    bind_coupon_form();
});

bind_site_center = function() {
    var subject, selector, form, callback;

    subject = '.site-center';
    selector = '.delivery-info-form .submit-button';
    form = '.delivery-info-form';

    callback = function(event) {
        Cart.submitOrder($(this).closest(form));
        event.stopPropagation();
        event.preventDefault();
        return false;
    };

    $('body').on('click', selector, callback);
}

bind_address_change = function(place_id,
                               change_translation,
                               cancel_translation,
                               find_address_and_recount_url,
                               take_translation,
                               show_places_translation,
                               place_url,
                               places_url,
                               success_callback) {
    var subject, callback;

    subject = '.address-change';

    callback = function(e) {
        var target_is_span,
            target_is_input,
            click_callback,
            data_callback;

        target_is_span = $(e.target).is('span');
        target_is_input = $(e.target).is('input');

        if (!target_is_span && !target_is_input) return false;

        click_callback = function(options) {

            if (options.response.data.success == 1 &&
                options.response.data.adr == 1)
            {
                if (options.response.data.nodelivery == 1) {
                    options.dialog.parent().unmask();
                    options.dialog.dialog('close');
                    toTakeOrNotToTake(place_id,
                                      take_translation,
                                      show_places_translation,
                                      place_url,
                                      places_url);
                } else {
                    options.dialog.parent().unmask();
                    options.dialog.dialog('close');

                    success_callback();
                }
            } else {
                options.dialog.parent().unmask();
                options.alert.show();
                setTimeout(function(){
                    options.alert.hide();
                }, 5000);
            }
        };

        data_callback = function() {
            return { city: $('.city-row select').val(),
                     address: $('.address-row input').val(),
                     place: place_id };
        };

        change_location($('#change-address'),
                        click_callback,
                        data_callback,
                        change_translation,
                        cancel_translation,
                        find_address_and_recount_url);
    };

    $(subject).click(callback);
}

bind_tooltip = function() {
    var subject;

    subject = '.tooltip';

    $(subject).tooltip();
}

bind_coupon_form = function() {
    var subject, callback;

    subject = '#coupon_form';

    callback = function() {
        $('.coupon_form_popup .button-submit').trigger('click');
        return false;
    };

    $(subject).bind('submit', callback);
}

bind_coupon_submit_button = function(place_id,
                                     check_coupon_url,
                                     cart_refresh_url) {
    var subject, callback, success_callback, popupInner;

    subject = '.coupon_form_popup .button-submit';
    popupInner = $('.coupon_inner');
    var couponField = $('input#coupon_code_popup');

    success_callback = function(resp){
        if (resp.status == 1) {
            var takeAway, options, post_callback;

            takeAway = 0;

            if ($('input[name="delivery-type"]:checked').val() == 'pickup') {
                takeAway = 1;
            }

            options = { place: place_id,
                        in_cart: 1,
                        coupon_code: couponField.val(),
                        take_away: takeAway };

            post_callback = function(response) {
                if (typeof(response.block) != "undefined") {
                    $('.check-block').replaceWith(response.block);
                }

                popupInner.unmask();

                // move data to main form
                $('#coupon_code').val(couponField.val());
                couponField.val('');
                popupInner.find('.alert.alert-danger').remove();
                $.fancybox.close();
            };

            $.post(cart_refresh_url, options, post_callback, 'json');
        } else {
            var dangerAlert = popupInner.find('.alert.alert-danger');

            if (dangerAlert.length > 0) {
                dangerAlert.html(resp.data.error);
            } else {
                popupInner.prepend('<div class="alert alert-danger">'+resp.data.error+'</div>');
            }

            popupInner.unmask();
        }
    };

    callback = function() {
        var couponField, popupInner, options;

        couponField = $('input#coupon_code_popup');
        popupInner = $('.coupon_inner');
        
        popupInner.mask();

        options = {
            type: 'GET',
            url: check_coupon_url,
            data: { place_id: place_id,
                    coupon_code: couponField.val() },
            success: success_callback
        };

        $.ajax(options);
    };
    
    $(subject).bind('click', callback);
}

function toTakeOrNotToTake(place_id,
                           take_translation,
                           show_places_translation,
                           place_url,
                           places_url) {
    var subject, options;

    subject = '#place-point-not-found';

    options = {
        modal: true,
        resizable:false,
        width: 360,
        buttons: {
            'i_take_it': {
                text: take_translation,
                click: function() {
                    window.location.href = place_url;
                },
            },
            'show_the_list': {
                text: show_places_translation,
                click: function() {
                    $(this).parent().mask();
                    window.location.href = places_url;
                }
            }
        }
    };

    $(subject).dialog(options).siblings('.ui-dialog-titlebar').remove();
}
