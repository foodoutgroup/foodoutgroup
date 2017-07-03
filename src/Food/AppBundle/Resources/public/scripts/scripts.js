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
                $("#boxer input:not(.no-icheck)").iCheck();
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

    $('.lang-chose').click(function (event) {
        $('.lang-drop ul').slideToggle(500);
        event.preventDefault();
        return false;
    });

    $(document).bind('click', function (e) {
        if (!$(e.target).parents().hasClass("lang-drop"))
            $(".lang-drop ul").hide();
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
(function (form) {

    var input_auto_complete = form.find('#address_autocomplete');
    var button_submit = form.find('#submit');
    var input_collection = form.find('input');
    var button_find_me = form.find('#find-me');
    var button_do_pickup = form.find('#do-pickup');
    var div_error = form.find('#error');
    var mapDiv = form.find("#map");
    var mapErrorDiv = form.find("#mapError");
    var map = null;
    var marker = null;
    var resultCollection = [];
    var selected = null;

    if (!navigator.geolocation) {
        button_find_me.remove();
    }
    var autoSelect = true;
    input_auto_complete.autocomplete({
        source: input_auto_complete.data('url'),
        minLength: 2,
        html: true,
        position: {
            my: "left+0 top-4"
        },
        response: function( event, ui ) {
            resultCollection = ui.content;
        },
        select: function( event, ui ) {
            setSelected(ui.item);
            autoSelect = false;
        }
    }).focusin(function(){
        autoSelect = true;
        if(resultCollection.length >= 2) {
            $(this).autocomplete("search");
        }
    }).focusout(function(){
        if(autoSelect && resultCollection.length >= 1 && autoSelect) {
            setSelected(resultCollection[0]);
        }
        autoSelect = false;
    }).data("ui-autocomplete")._renderItem = function (ul, item) {
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a data-class='" + item.class + "'>" + item.label + "</a>")
            .appendTo(ul);
    };

    button_find_me.click(function (e) {
        e.preventDefault();
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position){

                $.get(button_find_me.data("url"), {"lat": position.coords.latitude, "lng": position.coords.longitude}, function (response) {
                    setSelected({'id': response.detail.id, 'value': response.detail.output});
                });
                if(position.coords.accuracy > 150) {
                    throwMapLocationPicker(position.coords.latitude, position.coords.longitude, button_find_me.data('error-accuracy-to-big'));
                }
            });
        } else {
            throwError(button_find_me.data('error-no-service'));
        }
    });

    button_do_pickup.click(function (e) {
        e.preventDefault();
        img = $(this).find('img');
        oldval = img.attr('src');
        img.attr('src', img.attr('img-loader'));
        $.get($(this).data("url"), {"redirect" : true, "type" : "pickup", "address": input_auto_complete.data("selected") }, function (response) {
            if(response.success) {
                window.location.href = response.url;
            } else {
                throwError(response.message);
            }
            img.attr('src', oldval);
        });

    });

    input_collection.on('keyup', function (e) {
        if(e.keyCode != 13) {
            throwError(null);
        }
    }).on('focusin', function () {
        throwError(null);
    });

    div_error.click(function () {
        throwError(null);
    });

    button_submit.click(function (e) {
        e.preventDefault();
        img = $(this).find('img');
        oldval = img.attr('src');
        input_auto_complete.attr('disabled', true);

        img.attr('src', img.attr('img-loader'));

        $.post($(this).data('url'), {"address":input_auto_complete.data('selected'), "flat" : form.find('#flat').val()} , function (response) {
            if(response.success && typeof response.url != "undefined" ) {
                if(button_submit.data('redirect') == "self"){
                    window.location.href = window.location.href;
                } else {
                    window.location.href = response.url;
                }
            } else {
                if(typeof response.detail != "undefined" && response.detail.precision < 5) {
                    throwMapLocationPicker(response.detail.latitude, response.detail.longitude, response.message);
                } else {
                    throwError(response.message);
                }
                input_auto_complete.attr('disabled', false);
                img.attr('src', oldval);
            }
        });

    });


    mapErrorDiv.click(function () {
       $(this).addClass('hidden');
       $(this).html('');
    });

    function setSelected(selected) {
        input_auto_complete.data('selected', selected.id);
        input_auto_complete.val(selected.value);
        form.find('#hidden-field-for-address-id').val(selected.id);
    }

    function throwMapLocationPicker(lat, lng, message) {

        if(message != null) {
            mapErrorDiv.removeClass('hidden');
            mapErrorDiv.html(message);
        }

        if(lat != null && lng != null) {

            mapDiv.removeClass('hidden');
            var pt = new google.maps.LatLng(lat, lng);

            if(map == null) {
                map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 8,
                    maxZoom: 17,
                    disableDefaultUI: true,
                    minZoom: 10,
                    center: pt,
                    zoomControl: true,
                    zoomControlOptions: {
                        style: google.maps.ZoomControlStyle.LARGE
                    },
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });
            }
            map.setZoom(16);
            map.setCenter(pt);

            if(marker != null) {
                marker.setMap(null);
                marker = null;
            }

            marker = new google.maps.Marker({map: map, draggable:true, animation: google.maps.Animation.DROP, position: pt});
            google.maps.event.addListener(marker, 'mouseover', function() {
                mapErrorDiv.addClass('hidden');
                mapErrorDiv.html('');
            });
            google.maps.event.addListener(marker, 'dragend', function() {
                $.get(button_find_me.data("url"), {"lat": marker.getPosition().lat(), "lng": marker.getPosition().lng()}, function (response) {
                    setSelected({'id': response.detail.id, 'value': response.detail.output});
                });
            });
        }
    }

    function throwError(message){
        if(message == null) {
            message = '';
            mapDiv.addClass('hidden');
            mapErrorDiv.addClass('hidden');
            mapErrorDiv.html('');
            input_collection.removeClass('error');
        } else {
            form.closest('.shake-me').shake(5, 5, 400);
            input_collection.addClass('error');
        }
        div_error.html(message);
    }

})($( ".address-search-form-ui" ));


jQuery.fn.shake = function(intShakes, intDistance, intDuration) {
    this.each(function() {
        $(this).css("position","relative");
        for (var x=1; x<=intShakes; x++) {
            $(this).animate({left:(intDistance*-1)}, (((intDuration/intShakes)/4)))
                .animate({left:intDistance}, ((intDuration/intShakes)/2))
                .animate({left:0}, (((intDuration/intShakes)/4)));
        }
    });
    return this;
};


