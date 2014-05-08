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
});

var Place = {
    listTypeSaveUrl: '',
    placePointDataUrl: '',
    lastPointData: null,
    informationTabOpened: false,
    translations: {
        'payments_cash': 'grynais',
        'payments_card': 'kortele'
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

        init_raty();
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
                    var payments = '';
                    if (data.allowCash) {
                        payments += Place.translations.payments_cash;
                    }
                    if (data.allowCard) {
                        if (payments != '') {
                            payments += ', ';
                        }
                        payments += Place.translations.payments_card;
                    }
                    var paymentsDataField  =$('.ico-payments');
                    paymentsDataField.find('.payments-data').html(payments);
                    if (paymentsDataField.hasClass('hidden')) {
                        paymentsDataField.removeClass('hidden');
                    }


                    var workTimesHolder = $('.work-times');

                    workTimesHolder.find('.wd1 span').html(data.workTime.wd1_start + ' - ' + data.workTime.wd1_end);
                    workTimesHolder.find('.wd2 span').html(data.workTime.wd2_start + ' - ' + data.workTime.wd2_end);
                    workTimesHolder.find('.wd3 span').html(data.workTime.wd3_start + ' - ' + data.workTime.wd3_end);
                    workTimesHolder.find('.wd4 span').html(data.workTime.wd4_start + ' - ' + data.workTime.wd4_end);
                    workTimesHolder.find('.wd5 span').html(data.workTime.wd5_start + ' - ' + data.workTime.wd5_end);
                    workTimesHolder.find('.wd6 span').html(data.workTime.wd6_start + ' - ' + data.workTime.wd6_end);
                    workTimesHolder.find('.wd7 span').html(data.workTime.wd7_start + ' - ' + data.workTime.wd7_end);

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
    catmenuOffset: {},
    contentHeight: 0,
    cartObj: null,
    initWindowScroll: function() {
        this.catmenuObj = $("#catmnu");
        this.cartObj = $("#cartmnu");
        this.catmenuOffset = this.catmenuObj.offset();
        this.catmenuHeight = this.catmenuObj.height();
        this.contentHeight = $('#detailed-restaurant-menu').height()-30;
        $(window).bind('scroll', function(){
            Place.moveBlocks();
        });
    },
    moveBlocks: function() {
        var scTop = $(document).scrollTop();
        var newTop = scTop - this.catmenuOffset.top;
        if (this.catmenuOffset.top < scTop+80) {
            if (newTop + this.catmenuHeight > this.contentHeight) {
                newTop = this.contentHeight - this.catmenuHeight;
            }
            if(this.catmenuOffset.top < scTop) {
                this.catmenuObj.css('top', newTop);
            }
            this.cartObj.css('margin-top', newTop+80);
        } else {
            this.catmenuObj.css('top', 0);
            this.cartObj.css('margin-top', 0);
        }
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