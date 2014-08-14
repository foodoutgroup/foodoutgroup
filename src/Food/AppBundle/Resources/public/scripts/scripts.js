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
        var callback, data, form, url, form_rows, dataType;

        form = $(this);
        url = form.attr('action');
        data = form.serialize();
        form_rows = $('.review-form .form-row')
        dataType = 'json';

        form_rows.removeClass('error');

        callback = function(response) {
            if (response.success == 1) {
                top.location.reload();
            } else {
                form_rows.addClass('error');
            }
        };

        $.post(url, data, callback, dataType);

        return false;
    });
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