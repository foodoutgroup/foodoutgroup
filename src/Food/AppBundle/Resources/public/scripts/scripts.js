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
    });
})(jQuery, window);

bind_registration_form = function() {
    $('body').on('submit', '.righter.register-form', function(e) {
        var callback, data, form, url;

        form = $(this);
        url = form.attr('action');
        data = form.serialize();

        callback = function(response) {
            if (response.length > 0) {
                return $('.registration_form_wrapper:visible').html(response);
            } else {
                return top.location.href = top.location.href;
            }
        };

        $.post(url, data, callback);

        return false;
    });
}

bind_login_form = function() {
    $('body').on('submit', '.lefter.login-form', function(e) {
        var callback, data, form, url;

        form = $(this);
        url = form.attr('action');
        data = form.serialize();
        form_login_rows = $('.login-form-row')
        dataType = 'json';

        form_login_rows.removeClass('error');

        callback = function(response) {
            if (response.success == 1) {
                return top.location.href = top.location.href;
            } else {
                form_login_rows.addClass('error');
                form.find('input[password]').val('');
            }
        };

        $.post(url, data, callback, dataType);

        return false;
    });
}
