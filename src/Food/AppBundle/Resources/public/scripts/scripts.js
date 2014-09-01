(function($, window){
    $(function() {
        var $window = $(window);

        /*Placeholder for old browsers*/
        $('input[placeholder], textarea[placeholder]').placeholder();

        /*JS PIE. Fetures and usage: http://css3pie.com/documentation/supported-css3-features/*/
        if (window.PIE) {
            $('.rounded').each(function() {
                PIE.attach(this);
            });
        }

        resizeSensitive();

        $window.resize(function () {
            resizeSensitive();
        });

        // Boxer lightbox plugin

        bind_custom_select();

        $("input").iCheck();


        $(".boxer").boxer({
            callback: function(){
                $("input").iCheck();
                $('form.login-form input[name=_username]').focus();
            }
        });


        $( "#detailed-restaurant" ).tabs({
            activate: function( event, ui ) {
                ui.newTab.trigger('tab-activate');
            }
        });


        $('.restoran-rating').raty({
            readOnly: true,
            score: function() {
                return $(this).attr('data-stars');
            },
            path: '/bundles/foodapp/images/'
        });

        function resizeSensitive() {

        }

        bind_registration_form();
        bind_login_form();
        bind_review_form();
        bind_profile_menu_items();
        bind_show_password_resetting_form();
        bind_password_resetting_form();
    });
})(jQuery, window);

bind_custom_select = function() {
    var custom_select;

    custom_select = $('.custom-select');
    custom_select.selectmenu();
}

init_raty = function() {
    var selector, options;

    selector = '.place-review-popup-wrapper .rate-review:empty';
    options = {
        path: '/bundles/foodapp/images/'
    };

    $(selector).raty(options);
}

bind_registration_form = function() {
    $('body').on('submit', '.righter.register-form', function(e) {
        var callback, data, form, url;

        form = $(this);
        submit_btn = form.find('input[type=submit]');
        url = form.attr('action');
        data = form.serialize();

        form.mask();

        callback = function(response) {
            if (response.length > 0) {
                $('.registration_form_wrapper:visible').html(response);
                form.unmask();

                var inputs = $('.register-form:visible .form-row input');
                inputs.tooltip('show');
            } else {
                top.location.href = top.location.href;
            }
        };

        $.post(url, data, callback);

        return false;
    });
}

bind_login_form = function() {
    $('body').on('submit', '.lefter.login-form', function(e) {
        var callback, data, form, url, error;

        form = $(this);
        url = form.attr('action');
        data = form.serialize();
        form_login_rows = $('.login-form-row');
        error = form.find('.login-error')
        dataType = 'json';

        error.hide();
        form_login_rows.removeClass('error');
        form.closest('.login-register-popup').mask();

        callback = function(response) {
            if (response.success == 1) {
                top.location.href = top.location.href;
            } else {
                form_login_rows.addClass('error');
                form.find('input[password]').val('');
                error.show();
                form.closest('.login-register-popup').unmask();
            }
        };

        $.post(url, data, callback, dataType);

        return false;
    });
}

bind_profile_menu_items = function() {
    var menu_items, content_items;

    menu_items = $('.user-page .user-menu .menu-item');
    content_items = $('.user-page .content-item');

    menu_items.click(function() {
        menu_items.removeClass('active');
        content_items.hide();

        $(this).addClass('active');
        $($(this).attr('data-target')).show();

        return false;
    });
}

bind_review_form = function() {
    $('body').on('submit', '.review-form', function(e) {
        var callback, data, form, url, form_rows, data_type;

        form = $(this);
        url = form.attr('action');
        data = form.serialize();
        form_rows = $('.review-form .form-row');
        data_type = 'json';

        form_rows.removeClass('error');

        callback = function(response) {
            if (response.success == 1) {
                top.location.reload();
            } else {
                form_rows.addClass('error');
            }
        };

        $.post(url, data, callback, data_type);

        return false;
    });
}

bind_show_password_resetting_form = function() {
    $('body').on('click', '.reset_password_btn', function(e) {
        $('.login_form_wrapper').hide();
        $('.resetting_form_wrapper').show();

        return false;
    });

    $('body').on('click', '.back_to_login', function(e) {
        $('.resetting_form_wrapper').hide();
        $('.login_form_wrapper').show();

        return false;
    });

    return true;
};

bind_password_resetting_form = function() {
    $('body').on('submit', '.resetting_form_wrapper form', function(e) {
        var callback, data, form, popup, url, form_rows, data_type;

        form = $(this);
        popup = $('.popup.login-register-popup')
        url = form.attr('action');
        data = form.serialize();
        form_rows = $('.resetting_form_wrapper form .form-row');
        data_type = 'json';

        form_rows.removeClass('error');
        popup.mask();

        callback = function(response) {
            if (response.success == 1) {
                top.location = form.attr('data-redirect-target');
            } else {
                form_rows.addClass('error');
                popup.unmask();
            }
        };

        $.post(url, data, callback, data_type);

        return false;
    });
}

change_location = function(element,
                           change_text,
                           cancel_text,
                           request_url,
                           success_url) {
    var dialog_options,
        change_options,
        cancel_options,
        city_select,
        address_select;

    city_select = $('.city-row select');
    address_select = $('.address-row input');

    change_options = {
        text: change_text,
        click: function() {
            var thisDialog,
                options,
                alert;

            thisDialog = $(this);
            thisDialog.parent().mask();

            alert = element.find('.alert');

            options = {
                type: 'GET',
                url: request_url,
                data: { city: city_select.val(),
                        address: address_select.val() },
                success: function(response){
                    if (response.data.success == 1) {
                        window.location = success_url;
                    } else {
                        thisDialog.parent().unmask();
                        alert.show();

                        setTimeout(function(){
                            alert.hide();
                        }, 5000);
                    }
                }
            }

            $.ajax(options);
        }
    };

    cancel_options = {
        text: cancel_text,
        click: function() {
            $(this).dialog('close');
        }
    };

    dialog_options = {
        modal: true,
        resizeable: false,
        width: 360,
        buttons: {
            'change': change_options,
            'cancel': cancel_options
        }
    };

    element.dialog(dialog_options)
           .siblings('.ui-dialog-titlebar')
           .remove();
}
