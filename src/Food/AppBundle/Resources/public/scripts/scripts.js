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
                top.location.href = top.location.href.replace('#', '');
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
                top.location.href = top.location.href.replace('#', '');
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

// 'click_callback' is needed since it's function may differ from page to page.
// 'data_callback' is needed since we need to lazy evaluate data from inputs at
// the very last moment.
change_location = function(element,
                           click_callback,
                           data_callback,
                           change_text,
                           cancel_text,
                           request_url)
{
    var dialog_options,
        change_options,
        cancel_options;

    change_options = {
        text: change_text,
        click: function() {
            var dialog,
                options,
                alert;

            dialog = $(this);
            dialog.parent().mask();

            alert = element.find('.alert');

            options = {
                type: 'GET',
                url: request_url,
                data: data_callback(),
                success: function(response){
                    click_callback({response: response,
                                    dialog: dialog,
                                    alert: alert});
                }
            }

            $.ajax(options);
        }
    };

    /*
    cancel_options = {
        text: cancel_text,
        click: function() {
            $(this).dialog('close');
        }
    };
    */
    if (element.find('.submit-button').size() == 0) {
        //element.append('<a href="#" class="submit-button btn-cancel no-arrow no-arrow-second"><span>'+ cancel_options.text +'</span></a>');
        element.append('<div class="form-row address-row width463"><label></label><button class="button-normal submit">'+ change_options.text +'</button></div>');
    }

    element.find('.btn-cancel').unbind('click').bind('click', function(){
        $.fancybox.close();
    });

    element.find('.submit').unbind('click').bind('click', function(){
        element.mask();
        alert = element.find('.alert');

        options = {
            type: 'GET',
            url: request_url,
            data: data_callback(),
            success: function(response){
                click_callback({response: response,
                    dialog: element,
                    alert: alert}
                );
            }
        }
        $.ajax(options);
    });

    $.fancybox({
        'autoScale': true,
        'transitionIn': 'elastic',
        'transitionOut': 'elastic',
        'speedIn': 500,
        'speedOut': 300,
        'autoDimensions': true,
        'centerOnScroll': true,
        //'modal': true,
        'content' : element
    });

    /*
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
    */
}

initStreetSearch = function(){
    var streetsUrl = Routing.generate('food_ajax', { '_locale': 'lt', 'action' : 'find-street' });
    var streets = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: '?city=%CITY&street=%QUERY',
            replace: function(url, query) {
                url = url.replace('%CITY', $('#index_city').val());
                url = url.replace('%QUERY', query);
                return streetsUrl + url;
            }
        }
    });

    streets.initialize();

    $('#index_address').typeahead(null, {
        name: 'streets',
        displayKey: 'value',
        source: streets.ttAdapter()
    });
}
initStreetHouseSearch = function(){
    var streetsUrl = Routing.generate('food_ajax', { '_locale': 'lt', 'action' : 'find-street-house' });
    var streets = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: '?city=%CITY&street=%STREET&house=%QUERY',
            replace: function(url, query) {
                url = url.replace('%CITY', $('#index_city').val());
                url = url.replace('%STREET', $('#index_address').val());
                url = url.replace('%QUERY', query);
                return streetsUrl + url;
            }
        }
    });

    streets.initialize();

    $('#index_house').typeahead(null, {
        name: 'houses',
        displayKey: 'value',
        source: streets.ttAdapter()
    });
}
