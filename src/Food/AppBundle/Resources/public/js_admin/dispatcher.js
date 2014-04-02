var Dispatcher = {
    _locale: 'lt',
    _translations: {},

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

        $(".order_list.unassigned .order_checkbox").bind('click', function(){
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
                        "Keisti": function() {
                            var newStatus = $(this).find('.order_status:checked').val();
                            var url = Routing.generate('food_admin_set_order_status', { '_locale': Dispatcher._locale, 'orderId': orderId, 'status': newStatus, _sonata_admin: 'sonata.admin.dish' });
                            $.get(
                                url,
                                function(data) {
                                    // TODO error handlingas
                                    console.log('succesas?');
                                }
                            );

                            // TODO refresh the page!!!!
                            $( this ).dialog( "close" );
                            $( this ).dialog( "destroy" );
                        },
                        "Atšaukti": function() {
                            $( this ).dialog( "close" );
                            $( this ).dialog( "destroy" );
                        }
                    }
                }).dialog('open');
            }
        });
    },

    getDriversList: function(button) {
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
            }
        );
    },

    assignDriver: function(driverId) {
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
                console.log('-- succeeded');
                location.reload();
            }
        );
    }
};