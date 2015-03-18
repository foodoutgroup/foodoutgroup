var Dispatcher = {
    _locale: 'lt',
    _translations: {},
    recentOrders: {},

    setLocale: function(locale) {
        Dispatcher._locale = locale;
    },

    setTranslation: function(key, value) {
        Dispatcher._translations[key] = value;
    },

    getTranslation: function(key) {
        if (typeof Dispatcher._translations[key] == "undefined") {
            return key;
        }

        return Dispatcher._translations[key];
    },

    onLoadEvents: function() {
        $('.city_list').tabs();

        // Visus stripintus tekstus ispiesim tooltipe :)
        $(".spliced-text").tooltip({});

        // Vairuotojo papildoma info :)
        $(".driver-info-extended").tooltip({
            tooltipClass: 'driver-tooltip'
        });

        // Vairuotojo papildoma info :)
        $(".status-change-history").tooltip({
            placement: 'right'
        });

        // Vairuotojo papildoma info :)
        $(".restourant-phones").tooltip({
            placement: 'right'
        });

        $(".city-tab a").tooltip({});

        $(".order_list.unassigned .order_checkbox, .order_list.not_finished .order_checkbox").bind('click', function(){
            Dispatcher.toggleDriverButton($(this));
        });

        $(".change_status_button").bind('click', function() {
            Dispatcher.showStatusPopup($(this));
        });

        $(".get_drivers_button ").bind('click', function() {
            Dispatcher.getDriversList($(this));
        });

        $('.drivers_list').delegate('.assign-driver', 'click', function() {
            Dispatcher.assignDriver($(this).attr('item-id'));
        });

        Dispatcher.subscribeForNewOrders();
    },

    toggleDriverButton: function(checkbox) {
        var activeList = checkbox.closest(".order_list");
        var button = activeList.parent().find('.get_drivers_button');
        var checkedBoxes = activeList.find('.order_checkbox:checked');

        if (checkedBoxes.size() > 0) {
            button.attr('disabled', false);
        } else {
            button.attr('disabled', true);
        }
    },

    showStatusPopup: function(button) {
        var orderId = button.attr('item-id');
        var url = Routing.generate('food_admin_get_order_status_popup', { '_locale': Dispatcher._locale, 'orderId': orderId, _sonata_admin: 'sonata.admin.dish' });
        var tag = $("<div></div>");

        $.ajax({
            url: url,
            success: function(data) {
                tag.html(data).dialog({
                    title: Dispatcher.getTranslation('change_status_title'),
                    resizable: false,
                    modal: true,
                    buttons: {
                        // translate buttons
                        "Ok": function() {
                            var newStatus = $(this).find('.order_status:checked').val();
                            var url = Routing.generate('food_admin_set_order_status', { '_locale': Dispatcher._locale, 'orderId': orderId, 'status': newStatus, _sonata_admin: 'sonata.admin.dish' });
                            $.get(
                                url,
                                function(data) {
                                    // TODO error handlingas
//                                    console.log('succesas?');
                                    location.reload();
                                }
                            );

                            // TODO refresh the page!!!!
                            $( this ).dialog( "close" );
                            $( this ).dialog( "destroy" );
                        },
                        "Cancel": function() {
                            $( this ).dialog( "close" );
                            $( this ).dialog( "destroy" );
                        }
                    }
                }).dialog('open');
            }
        });
    },

    getDriversList: function(button) {
        $('.city_list').mask();

        var activePanel =  button.closest('.ui-tabs-panel');
        var activeList = activePanel.find(".order_list");
        var checkedBoxes = activeList.find('.order_checkbox:checked');
        var orderIds = [];

        checkedBoxes.each(function(key, value){
            orderIds.push($(value).val());
        });

        var url = Routing.generate('food_admin_get_driver_list', { '_locale': Dispatcher._locale, 'orders': orderIds, _sonata_admin: 'sonata.admin.dish' });

        $.get(
            url,
            function(data) {
                $('.drivers_list').html(data);
                $('.city_list').unmask();
            }
        );
    },

    assignDriver: function(driverId) {
        $('.sonata-ba-list').mask();
        var activeList = $('.order_list:visible');
        var checkedBoxes = activeList.find('.order_checkbox:checked');
        var orderIds = [];

        checkedBoxes.each(function(key, value){
            orderIds.push($(value).val());
        });

        var url = Routing.generate('food_admin_assign_driver', { '_locale': Dispatcher._locale, _sonata_admin: 'sonata.admin.dish' });
        $.post(
            url,
            {
                driverId: driverId,
                orderIds: orderIds
            },
            function (data) {
//                console.log('-- succeeded');
                location.reload();
            }
        );
    },

    subscribeForNewOrders: function() {
        setTimeout(
            function() {
                Dispatcher.checkForNewOrders();
            },
            60000
        );
    },

    checkForNewOrders: function() {
//        var activeList = $('.order_list:visible').attr('list-type');
        var url = Routing.generate('food_admin_check_new_orders', { '_locale': Dispatcher._locale, _sonata_admin: 'sonata.admin.dish' });
        $.get(
            url,
            { 'orders': Dispatcher.recentOrders},
            function(data) {
                if (data == "YES") {
                    location.reload();
                }

                Dispatcher.subscribeForNewOrders();
            }
        );
    }
};