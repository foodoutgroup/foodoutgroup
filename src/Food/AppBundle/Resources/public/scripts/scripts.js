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

        $('.custom-select').selectmenu();

        $("input").iCheck();


        $(".boxer").boxer({
            callback: function(){
                $("input").iCheck();
                $('form.login-form input[name=_username]').focus();
                init_raty();
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
    });
})(jQuery, window);

init_raty = function() {
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
        form.mask();

        callback = function(response) {
            if (response.success == 1) {
                top.location.href = top.location.href;
            } else {
                form_login_rows.addClass('error');
                form.find('input[password]').val('');
                error.show();
                form.unmask();
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
