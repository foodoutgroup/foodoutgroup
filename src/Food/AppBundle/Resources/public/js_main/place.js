$(document).ready(function() {
    Place.bindEvents();
    var placePointSelect = $('#detailed-restaurant-info .restaurant-info-righter .custom-select');
    if (typeof placePointSelect != "undefined" && placePointSelect.length) {
        placePointSelect.trigger('change');
    }
    // Visual refresh - dont know what it is, but just leave it here
    google.maps.visualRefresh = true;

    $( ".restaurant-info-tab" ).on( "tab-activate", function() {
        if (!Place.informationTabOpened) {
            Place.informationTabOpened = true;
            Place.showPointMap(Place.lastPointData, true);
        }
    } );

    // Categories dropdown max-height
    var scrollable_menu = $('.scrollable-menu');
    if (typeof scrollable_menu != "undefined" && scrollable_menu.length) {
        scrollable_menu.css('max-height', $(window.top).height() - 365);
    }

    // Dish Search Begin
    $('.dish_category_select').chosen({
        width: "100%",
        allow_single_deselect: true
    });

    $('.dish_category_select').change(function(){
        var category_id = $(this).find('option:selected').val();
        $(".category_name").each(function(){
            if ($(this).data('category') == category_id) {
                $(this).show();
                $('.restaurant-menu[data-category="' + $(this).data('category') + '"]').show();
            } else if (category_id == '') {
                $('.category_name').show();
                $('.restaurant-menu').show();
            } else {
                $(this).hide();
                $('.restaurant-menu[data-category="' + $(this).data('category') + '"]').hide();
            }
        });
    });

    var do_search_in_dishes = function(dish, filter, find_in, found_in_categories) {
        var dish_name = do_filter_string(dish.find(find_in).text());
        var filtered = do_filter_string(filter);
        if (dish_name.search(new RegExp(filtered, "i")) < 0) {
            dish.fadeOut();
        } else {
            dish.show();
            found_in_categories.push(dish.data('category'));
        }
    };

    var do_filter_string = function(string) {
        var alphabet = {
            'Ą': 'A', 'ą': 'a',
            'Č': 'C', 'č': 'c',
            'Ę': 'E', 'ę': 'e',
            'Ė': 'E', 'ė': 'e',
            'Į': 'I', 'į': 'i',
            'Š': 'S', 'š': 's',
            'Ų': 'U', 'ų': 'u',
            'Ū': 'U', 'ū': 'u',
            'Ž': 'Z', 'ž': 'z',
            'Ā': 'A', 'ā': 'a',
            'Ē': 'E', 'ē': 'e',
            'Ģ': 'G', 'ģ': 'g',
            'Ī': 'I', 'ī': 'i',
            'Ķ': 'K', 'ķ': 'k',
            'Ļ': 'L', 'ļ': 'l',
            'Ņ': 'N', 'ņ': 'n'

        };
        return string.replace(new RegExp("(" + Object.keys(alphabet).map(function(i){
            return i.replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&")
        }).join("|") + ")", "g"), function(s){
            return alphabet[s]
        });
    };

    $(".dish_search_input").keyup(function(){
        var filter = $(this).val();
        var found_in_categories = [];
        var category_id = $('.dish_category_select').find('option:selected').val();
        $(".restaurant-menu-item").each(function(){
            if (category_id != '') {
                if (category_id == $(this).data('category')) {
                    do_search_in_dishes($(this), filter, '.name:visible', found_in_categories);
                }
            } else {
                do_search_in_dishes($(this), filter, '.name', found_in_categories);
            }
        });
        if (found_in_categories.length) {
            $('.category_name').hide();
            $.each(found_in_categories, function(index, value){
                $('.category_name').each(function(){
                    if ($(this).data('category') == value) {
                        $(this).show();
                    }
                });
            });
            $(".restaurant-menu-item:visible:nth-child(2n)").css({'margin-right': 0});
        }
    });
    // Dish Search End
});

var Place = {
    listTypeSaveUrl: '',
    placePointDataUrl: '',
    lastPointData: null,
    informationTabOpened: false,
    translations: {
        'payments_cash': 'grynais',
        'payments_card': 'kortele',
        'payments_online': 'pavedimu'
    },

    /**
     * Bind restaurant view actions
     */
    bindEvents: function() {
        var viewTypeHolder = $('.menu-righter .view-type');

        viewTypeHolder.find('.ico-thumbs').bind('click', function() {
            Place.switchMenuLayout('thumbs');

            return false;
        });
        viewTypeHolder.find('.ico-list').bind('click', function() {
            Place.switchMenuLayout('list');

            return false;
        });

        $('#detailed-restaurant-info .restaurant-info-righter .custom-select').bind('change', function() {
            Place.loadPlacePointData($(this).val());
        });

        $('.place-review-popup').delegate('.review-form', 'submit', function() {
            $('.popup.place-review-popup').mask();

            var form = $(this);

            $.ajax({
                type	: "POST",
                cache	: false,
                url		: $(this).attr('action'),
                data		: $(this).serializeArray(),
                success: function(data) {
                    if (data.success) {
                        form.find('.error').html('').hide();
                        $('.popup.place-review-popup').unmask();
                        location.reload();
                        $.fancybox.close();
                    } else {
                        if (typeof data.errors != "undefined") {
                            var errorText = '';
                            $.each(data.errors, function(element, error){
                                if (errorText != '') {
                                    errorText += "<br>";
                                }
                                errorText += error;
                            });
                            form.find('.error').html(errorText).show();
                            setTimeout(
                                function() {
                                    form.find('.error').hide().html('');
                                },
                                15000
                            )
                        }
                        $('.popup.place-review-popup').unmask();
                    }
                }
            });

            return false;
        });

        $('.delivery-time-info').tooltip();

        init_raty();
        $('.got-title').tooltip();
    },
    /**
     * Change restaurant menu layout
     *
     * @param {string} type
     */
    switchMenuLayout: function(type) {
        var viewTypeHolder = $('.menu-righter .view-type');
        var dishesMenu = $('.restaurant-menu');

        if (Place.listTypeSaveUrl != '') {
            $.ajax({
                url: Place.listTypeSaveUrl+'/'+type
            });
        }

        if (type == 'thumbs') {
            viewTypeHolder.find('.ico-thumbs').addClass('active');
            viewTypeHolder.find('.ico-list').removeClass('active');

            dishesMenu.removeClass('display-list');
            dishesMenu.find('.dish_larger').removeClass('hidden');
            dishesMenu.find('.dish_smaller').addClass('hidden');
        } else {
            viewTypeHolder.find('.ico-thumbs').removeClass('active');
            viewTypeHolder.find('.ico-list').addClass('active');

            dishesMenu.addClass('display-list');
            dishesMenu.find('.dish_larger').addClass('hidden');
            dishesMenu.find('.dish_smaller').removeClass('hidden');
        }
    },
    /**
     * Update place point information
     * @param {integer} pointId
     */
    loadPlacePointData: function(pointId) {
        // TODO error handling?
        if (Place.placePointDataUrl != '') {
            var parentHolder = $('.detailed-restaurant-info');

            parentHolder.mask();
            $.ajax({
                url: Place.placePointDataUrl+'/'+pointId,
                type: 'get',
                dataType: 'json',
                success: function(data) {
                    if (typeof data == "undefined" || data == '' || data == null) {
                        // dont brake stuff, please
                        return;
                    }

                    Place.lastPointData = data;
                    // TODO translation
                    var payments = [];

                    if (data.allowCash) payments.push(Place.translations.payments_cash);
                    if (data.allowCard) payments.push(Place.translations.payments_card);
                    if (data.allowInternetPayments) payments.push(Place.translations.payments_online);

                    var paymentsDataField = $('.ico-payments');
                    paymentsDataField.find('.payments-data')
                                     .html(payments.join(', '));

                    if (paymentsDataField.hasClass('hidden')) {
                        paymentsDataField.removeClass('hidden');
                    }

                    var pp_phone_holder = $('.pp_phone_holder');
                    if (data.phone) {
                        var pp_phone_link = pp_phone_holder.find('.pp_phone_link');
                        var phone_number = '+' + data.phone;
                        var phone_number_nice = phone_number.replace(/(\d\d\d)(\d\d\d)(\d\d\d\d)/, "$1 $2 $3");
                        pp_phone_link
                            .attr('href', 'callto:' + phone_number)
                            .attr('title', phone_number)
                            .find('b').html(phone_number_nice)
                        ;
                        pp_phone_holder.show();
                    }

                    var workTimesHolder = $('.work-times');

                    workTimesHolder.find('.wd1 span').html(data.workTime.wd1);
                    workTimesHolder.find('.wd2 span').html(data.workTime.wd2);
                    workTimesHolder.find('.wd3 span').html(data.workTime.wd3);
                    workTimesHolder.find('.wd4 span').html(data.workTime.wd4);
                    workTimesHolder.find('.wd5 span').html(data.workTime.wd5);
                    workTimesHolder.find('.wd6 span').html(data.workTime.wd6);
                    workTimesHolder.find('.wd7 span').html(data.workTime.wd7);

                    if (Place.informationTabOpened) {
                        Place.showPointMap(data, false);
                    }
                    parentHolder.unmask();
                },
                error: function() {
                    // TODO do something
                    parentHolder.unmask();
                }
            });
        }
    },
    /**
     * Update google map of place point
     * @param {object} placeData
     * @param {boolean} showLoadingMask
     */
    showPointMap: function(placeData, showLoadingMask) {
        if (typeof placeData == "undefined" || placeData == null) {
            return;
        }
        if (typeof showLoadingMask == "undefined") {
            showLoadingMask = false;
        }

        if (showLoadingMask) {
            var mapHolder = $('#restaurant-map-location');
            mapHolder.mask();
        }

        $('#restaurant-map-location').html('');
        var restaurantPossition = new google.maps.LatLng(placeData.lat, placeData.lon);
        var mapOptions = {
            center: restaurantPossition,
            zoom: 14,
            mapTypeControl: false,
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.SMALL
            },
            panControl: false,
            scaleControl: true
        };
        var map = new google.maps.Map(document.getElementById("restaurant-map-location"),
            mapOptions);
        var marker = new google.maps.Marker({
            position: restaurantPossition,
            map: map,
            title: placeData.placeName+' '+placeData.address
        });

        if (showLoadingMask) {
            google.maps.event.addListenerOnce(map, 'idle', function(){
                mapHolder.unmask();
            });
        }
    },
    catmenuObj: null,
    catmenuHeight: 0,
    cartHeight: 0,
    catmenuOffset: {},
    cartOffset: {},
    contentHeight: 0,
    cartObj: null,
    initWindowScroll: function() {
        this.catmenuObj = $("#catmnu");
        this.cartObj = $("#cartmnu");
        this.catmenuOffset = this.catmenuObj.offset();
        this.catmenuHeight = this.catmenuObj.height();
        this.cartOffset = this.cartObj.offset();
        this.cartHeight = this.cartObj.height();
        $(window).bind('scroll', function(){
            Place.moveBlocks();
        });
        Place.initCartScroll();
    },
    initCartScroll: function() {
        this.cartObj = $("#cartmnu");
        this.cartOffset = this.cartObj.offset();
        this.cartHeight = this.cartObj.height();
        this.cartObj.addClass('overflow_auto');
    },
    moveBlocks: function(init) {
        var windowTop = $(window).scrollTop();
        if (this.catmenuOffset.top < windowTop || (init == true && this.cartOffset.top < windowTop)) {
            this.catmenuObj.addClass('sticky');
        } else {
            this.catmenuObj.removeClass('sticky');
        }

        if (this.cartOffset.top < windowTop || (init == true && this.cartOffset.top < windowTop)) {
            this.cartObj.addClass('sticky');
        } else {
            this.cartObj.removeClass('sticky');
        }

        this.adjustCartMenuHeight();
    },
    adjustCartMenuHeight: function() {
        if($(window).scrollTop() + $(window).height() > $(document).height() - $('.site-footer').outerHeight() - 100) {
            this.setCartMenuHeight(true);
        } else {
            this.setCartMenuHeight(false);
        }
    },
    setCartMenuHeight: function(with_footer) {
        var top_margin = 45;
        var w_height = 0;
        if (with_footer) {
            w_height = $(window).height() - $('.site-footer').outerHeight();
        } else {
            w_height = $(window).height();
        }
        var new_height = (w_height >= top_margin ? w_height - top_margin : w_height);
        $("#cartmnu").height(new_height);
    }
}

/**
 * Itsy bitsy counter :D dieing in the streets....
 *
 * @param min
 * @param max
 * @param def
 */
$.fn.foodCounter = function(min,max,def) {
    this.addClass('theCounter').addClass('theCounter-input').data('min', min).data('max', max).data('def', def);
    this.before('<button class="theCounter-dec dec"><span class="ui-icon ui-icon-minus"></span></button>');
    this.after('<button class="theCounter-inc inc"><span class="ui-icon ui-icon-plus"></span></button>');
    if (this.val() == "") {
        this.val(def);
    }
    this.bind('inc', function(){
        var theVal = parseInt($(this).val(), 10);
        if (theVal + 1 > $(this).data('max')) {
            return;
        } else {
            $(this).val(theVal + 1);
        }
    });
    this.bind('dec', function(){
        var theVal = parseInt($(this).val(), 10);
        if (theVal - 1 < $(this).data('min')) {
            return;
        } else {
            $(this).val(theVal - 1);
        }
    });
    this.parent().find('.dec').bind('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        $(this).parent().find('.theCounter').trigger('dec');
    });
    this.parent().find('.inc').bind('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        $(this).parent().find('.theCounter').trigger('inc');
    });
};
