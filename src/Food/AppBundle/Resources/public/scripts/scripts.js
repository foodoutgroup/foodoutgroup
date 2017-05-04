(function($){
    $(function() {

        /*Placeholder for old browsers*/
        $('input[placeholder], textarea[placeholder]').placeholder();
        if (window.PIE) {
            $('.rounded').each(function() {
                PIE.attach(this);
            });
        }

        bind_custom_select();

        $("input:not(.no-icheck)").iCheck();


        $(".boxer").boxer({
            callback: function(){
                $("input:not(.no-icheck)").iCheck();
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

        bind_registration_form();
        bind_login_form();
        bind_review_form();
        bind_profile_menu_items();
        bind_show_password_resetting_form();
        bind_password_resetting_form();
    });

})(jQuery);

bind_custom_select = function() {
    var custom_select = $('.custom-select');
    custom_select.selectmenu();
};

init_raty = function() {
    $('.place-review-popup-wrapper .rate-review:empty').raty({path: '/bundles/foodapp/images/'});
};

bind_registration_form = function() {
    $('body').on('submit', '.righter.register-form', function() {
        var  form = $(this);
        form.mask();
        $.post(form.attr('action'), form.serialize(), function(response) {
            if (response.length > 0) {

                $('.registration_form_wrapper:visible').html(response);
                form.unmask();

                var checkboxs = $('.register-form:visible .form-row input[type="checkbox"]');
                checkboxs.iCheck();

                var inputs = $('.register-form:visible .form-row input');
                inputs.tooltip('show');
            } else {
                top.location.href = top.location.href.replace('#', '');
            }
        });

        return false;
    });
}

bind_login_form = function() {
    $('body').on('submit', '.lefter.login-form', function(e) {
        var form, error, form_login_rows;

        form = $(this);
        form_login_rows = $('.login-form-row');
        error = form.find('.login-error').hide();
        form_login_rows.removeClass('error');
        form.closest('.login-register-popup').mask();

        $.post(form.attr('action'), form.serialize(), function(response) {
            if (response.success == 1) {
                top.location.href = top.location.href.replace('#', '');
            } else {
                form_login_rows.addClass('error');
                form.find('input[password]').val('');
                error.show();
                form.closest('.login-register-popup').unmask();
            }
        }, 'json');

        return false;
    });
};

bind_profile_menu_items = function() {
    var menu_items = $('.user-page .user-menu .menu-item');

    menu_items.click(function() {
        menu_items.removeClass('active');
        $('.user-page .content-item').hide();

        $(this).addClass('active');
        $($(this).attr('data-target')).show();

        return false;
    });
};

bind_review_form = function() {
    $('body').on('submit', '.review-form', function(e) {
        var form, form_rows;

        form = $(this);
        form_rows = $('.review-form .form-row').removeClass('error');

        $.post(form.attr('action'),  form.serialize(), function(response) {
            if (response.success == 1) {
                top.location.reload();
            } else {
                form_rows.addClass('error');
            }
        }, 'json');

        return false;
    });
};

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
        var data, form, popup, form_rows;

        form = $(this);
        popup = $('.popup.login-register-popup');
        data = form.serialize();
        form_rows = $('.resetting_form_wrapper form .form-row');
        form_rows.removeClass('error');
        popup.mask();

        $.post(form.attr('action'), form.serialize(), function(response) {
            if (response.success == 1) {
                top.location = form.attr('data-redirect-target');
            } else {
                form_rows.addClass('error');
                popup.unmask();
            }
        }, 'json');
        return false;
    });
};


change_location = function(element, click_callback, data_callback, change_text, cancel_text, request_url) {

    var change_options = {
        text: change_text,
        click: function() {
            var dialog = $(this);
            $.ajax({type: 'GET', url: request_url, data: data_callback(), success: function(response){
                    click_callback({response: response, dialog: dialog.parent().mask(), alert: element.find('.alert')});
                }
            });
        }
    };

    if (element.find('.submit').size() == 0) {
        element.append('<div class="form-row address-row width463"><label></label><button class="button-normal submit">'+ change_options.text +'</button></div>');
    }

    element.find('.btn-cancel').unbind('click').bind('click', function(){
        $.fancybox.close();
    });

    element.find('.submit').unbind('click').bind('click', function(){
        element.mask();
        $.ajax({
            type: 'GET',
            url: request_url,
            data: data_callback(),
            success: function(response){
                click_callback({
                    response: response,
                    dialog: element,
                    alert: element.find('.alert')
                });
            }
        });
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
};

initStreetSearch = function(){
    var streetsUrl = Routing.generate('food_ajax', { '_locale': $('html').attr('lang'), 'action' : 'find-street' });
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
};

initStreetHouseSearch = function(){
    var streetsUrl = Routing.generate('food_ajax', { '_locale': $('html').attr('lang'), 'action' : 'find-street-house' });
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
};

var registrationForm = {
    showPrivate: function(element) {
        var theBox = $(element).closest('.popup');
        theBox.find('.button.private').addClass('selected');
        theBox.find('.button.company').removeClass('selected');
        var lefter = theBox.find('.lefter.login-form');
        theBox.find('.register-form.righter').removeClass('not-righter').removeClass('extended');
        theBox.find('#fos_user_registration_form_isBussinesClient').prop('checked', '');
        theBox.find('.inner-righter.righter.company').addClass('hidden');
        theBox.find('.tooltip').remove();
        theBox.find('.form-row.error').removeClass('error');
        lefter.show();
        return false;
    },
    showCompany: function(element) {
        var theBox = $(element).closest('.popup');
        theBox.find('.button.private').removeClass('selected');
        theBox.find('.button.company').addClass('selected');
        var lefter = theBox.find('.lefter.login-form');
        theBox.find('.register-form.righter').addClass('not-righter').addClass('extended');
        theBox.find('#fos_user_registration_form_isBussinesClient').prop('checked', 'checked');
        theBox.find('.inner-righter.righter.company').removeClass('hidden');
        theBox.find('.tooltip').remove();
        theBox.find('.form-row.error').removeClass('error');
        lefter.hide();
        return false;
    }
};