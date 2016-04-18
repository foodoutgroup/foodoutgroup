var Dispatcher = {
    _locale: 'lt',
    _translations: {},
    // recentOrders: {},
    lastCheck: '',
    bell: false,

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
        Dispatcher.initTabs();

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

        $(".city-tab a").tooltip({placement: 'bottom'});

        $(".todo_nieks_nezino_klases").on('click', ' .unassigned .order_checkbox,.not_finished .order_checkbox', function(){
            Dispatcher.toggleDriverButton($(this));
            //TODO - enable active drivers list buttons
        });

        $(".todo_nieks_nezino_klases").on('click', '.change_status_button', function() {
            Dispatcher.showStatusPopup($(this));
        });

        $(".todo_nieks_nezino_klases").on('click', '.sms_button', function() {
            Dispatcher.showSmsPopup($(this));
        });

        $(".todo_nieks_nezino_klases").on('click', '.approve_button', function() {
            $('.sonata-ba-list').mask();
            var orderId = $(this).attr('item-id');

            var url = Routing.generate('food_admin_approve_order', { '_locale': Dispatcher._locale, 'orderId': orderId, _sonata_admin: 'sonata.admin.dish' });
            $.get(
                url,
                function(data) {
                    $('.sonata-ba-list').unmask();
                    location.reload();
                }
            );
        });

        /* At the moment disabled. TODO - use this function to refresh drivers list
        $(".get_drivers_button ").bind('click', function() {
            Dispatcher.getDriversList($(this));
        });*/

        $('.drivers_list').delegate('.assign-driver', 'click', function() {
            Dispatcher.assignDriver($(this).attr('item-id'));
        });


        $(".order_list .client_contacted_check .client_contacted").bind('click', function(){
            Dispatcher.toggleClientContacted($(this));
        });

        $(".order_list .problem_solved_check .problem_solved").bind('click', function(){
            Dispatcher.toggleProblemSolved($(this));
        });

        Dispatcher.subscribeForNewOrders();

        // Preload sounds
        ion.sound({
            sounds: [
                {name: "door_bell"},
            ],

            // main config
            path: "/bundles/foodapp/js_admin/ion.sound/sounds/",
            preload: true,
            multiplay: true,
            volume: 0.9
        });
    },

    toggleDriverButton: function(checkbox) {
        var activeList = checkbox.closest(".order_list");
        // Old button
        //var button = activeList.parent().find('.get_drivers_button');
        var buttons = $('.drivers_list .city_drivers:visible').find('button');
        var checkedBoxes = activeList.find('.order_checkbox:checked');

        if (checkedBoxes.size() > 0) {
            buttons.attr('disabled', false);
        } else {
            buttons.attr('disabled', true);
        }
    },

    showStatusPopup: function(button) {
        $('.sonata-ba-list').mask();
        var orderId = button.attr('item-id');
        var url = Routing.generate('food_admin_get_order_status_popup', {
            '_locale': Dispatcher._locale,
            'orderId': orderId,
            _sonata_admin: 'sonata.admin.dish'
        });
        var tag = $("<div></div>");

        var statusButtons = {};
        var createEvent = {};

        statusButtons[Dispatcher.getTranslation('button_change')] = function() {
            var newStatus = $(this).find('.order_status:checked').val();
            var delayDuration = (newStatus == 'delayed' ? $(this).find('select#delay_duration').val() : null);
            var url = Routing.generate('food_admin_set_order_status', {
                '_locale': Dispatcher._locale,
                'orderId': orderId,
                'status': newStatus,
                'delayDuration': delayDuration,
                _sonata_admin: 'sonata.admin.dish'
            });
            $.get(
                url,
                function(data) {
                    location.reload();
                }
            );

            // TODO refresh the page!!!!
            $( this ).dialog( "close" );
            $( this ).dialog( "destroy" );
        };

        statusButtons[Dispatcher.getTranslation('button_cancel')] = function() {
            $( this ).dialog( "close" );
            $( this ).dialog( "destroy" );
        };

        createEvent = function(event, ui) {
            var fieldsHolder = $(this);
            var delayedFieldHolder = fieldsHolder.find('.delay_duration_holder');
            var statusFields = fieldsHolder.find('input[type="radio"].order_status');
            var delayedField = fieldsHolder.find('input[value="delayed"].order_status');
            var delayedStatus = (delayedField.prop('checked') ? true : false);

            var toggle_delayedFieldHolder = function(delayedStatus) {
                if (delayedStatus){
                    delayedFieldHolder.show();
                } else {
                    delayedFieldHolder.hide();
                }
            };

            toggle_delayedFieldHolder(delayedStatus);
            $(statusFields).on("click", function(event) {
                toggle_delayedFieldHolder(($(this).prop('checked') && $(this).val() == 'delayed' ? true : false));
            });
        };

        $.ajax({
            url: url,
            success: function(data) {
                $('.sonata-ba-list').unmask();
                tag.html(data).dialog({
                    title: Dispatcher.getTranslation('change_status_title'),
                    resizable: false,
                    modal: true,
                    buttons: statusButtons,
                    create: createEvent
                }).dialog('open');
            }
        });
    },

    showSmsPopup: function(button) {
        var orderId = button.attr('item-id');
        var tag = $("<div></div>");
        var data = $(".sms_message_popup").html();
        var statusButtons = {};

        statusButtons[Dispatcher.getTranslation('button_send')] = function() {
            $('.sonata-ba-list').mask();
            var message = $(this).find('.order_message').val();
            var url = Routing.generate('food_admin_send_message', { '_locale': Dispatcher._locale, 'orderId': orderId, 'message': message, _sonata_admin: 'sonata.admin.dish' });
            $.get(
                url,
                function(data) {
                    location.reload();
                }
            );

            // TODO refresh the page!!!!
            $( this ).dialog( "close" );
            $( this ).dialog( "destroy" );
        };

        statusButtons[Dispatcher.getTranslation('button_cancel')] = function() {
            $( this ).dialog( "close" );
            $( this ).dialog( "destroy" );
        };

        tag.html(data).dialog({
            title: Dispatcher.getTranslation('send_sms_title'),
            resizable: false,
            modal: true,
            buttons: statusButtons
        }).dialog('open');
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
            }, 30000
        );
    },

    checkForNewOrders: function() {
//        var activeList = $('.order_list:visible').attr('list-type');
        var url = Routing.generate('food_admin_check_new_orders', { '_locale': Dispatcher._locale, _sonata_admin: 'sonata.admin.dish' });
        $.get(
            url,
            { 'lastCheck': Dispatcher.lastCheck},
            function(data) {
                Dispatcher.lastCheck = data.lastCheck;
                if (data.needUpdate == "YES") {
                    // play a sound for new order
                    if (Dispatcher.bell) {
                        ion.sound.play("door_bell");
                    }
                    $('.sonata-ba-list').mask();
                    $.get(
                        window.location.href,
                        null,
                        function (data) {
                            Dispatcher.refreshFromData(data);
                            $('.sonata-ba-list').unmask();
                        }
                    )
                    // location.reload();
                }
            }
        )
            .always(function(){
                Dispatcher.subscribeForNewOrders();
            });
    },

    /**
     * Mark order as contacted with client after cancelation
     */
    toggleClientContacted: function(checkbox) {
        var url = Routing.generate('food_admin_mark_order_contacted', { '_locale': Dispatcher._locale, _sonata_admin: 'sonata.admin.dish' });
        var contactedStatus = 0;
        if (checkbox.is(":checked")) {
            contactedStatus = 1;
        }

        $.post(
            url,
            {
                'order': checkbox.attr('item-id'),
                'status': contactedStatus
            },
            function(data) {
                if (data == "YES") {
                    location.reload();
                }
            }
        );
    },

    /**
     * Mark order problem as solved
     */
    toggleProblemSolved: function(checkbox) {
        var url = Routing.generate('food_admin_mark_order_problem_solved', { '_locale': Dispatcher._locale, _sonata_admin: 'sonata.admin.dish' });
        var solvedStatus = 0;
        if (checkbox.is(":checked")) {
            solvedStatus = 1;
        }

        $.post(
            url,
            {
                'order': checkbox.attr('item-id'),
                'status': solvedStatus
            },
            function(data) {
                if (data == "YES") {
                    location.reload();
                }
            }
        );
    },
    
    initTabs: function() {
        $('.city_list').tabs();

        $('.city_list .city-tab a').on( "click", function( event ) {
            var city = $(event.target).closest('.city-tab').attr('data-city');
            var driversHolder = $('.drivers_list');

            // Wow - such hack.. Very sorry for this
            if (city.indexOf('Bendoriai') > -1) {
                city = 'Vilnius';
            }

            driversHolder.find('.city_drivers').addClass('hidden');
            driversHolder.find('.city_drivers.driver_'+city).removeClass('hidden');
        } );

        var total = $('.city-tab').length;

        for (i = 1; i <= total; ++i) {
            $('#city_list-' + i).tabs();
        }


    },

    refreshFromData: function(data) {
        $(".city-tab a").tooltip("destroy");
        var cityTabTotal = $('.city-tab').length;

        for (var cityTabIndex = 0; cityTabIndex < cityTabTotal; ++cityTabIndex) {
            // Current city tab
            var $currentCityTab = $($('.city-tab')[cityTabIndex]);

            // Current city tab content
            var $tabContent = $('#city_list-'+(cityTabIndex+1));

            // Inner tab list of current city
            var $orderTypeTabList = $tabContent.find('.orderTypeTabList li');

            // Tab content list of current city
            var $orderTypeContentList = $tabContent.find('div');

            // Same as above, just from ajax
            var $data_currentCityTab = $($(data).find('.city-tab')[cityTabIndex]);
            var $data_tabContent = $(data).find('#city_list-'+(cityTabIndex+1));
            var $data_orderTypeTabList = $data_tabContent.find('.orderTypeTabList li');
            var $data_orderTypeContentList = $data_tabContent.find('div');

            var orderTypeTotal = $orderTypeTabList.length;

            for (var orderTypeIndex = 0; orderTypeIndex < orderTypeTotal; ++orderTypeIndex) {
                // refreshing order type tab count of orders
                $($orderTypeTabList[orderTypeIndex]).find('.theCount').text($($data_orderTypeTabList[orderTypeIndex]).find('.theCount').text());
                $($orderTypeContentList[orderTypeIndex]).html($($data_orderTypeContentList[orderTypeIndex]).html());

                // refreshing order type tab exclamation signs
                if ($($data_orderTypeTabList[orderTypeIndex]).find('.glyphicon-exclamation-sign').length) {
                    if (!$($orderTypeTabList[orderTypeIndex]).find('.glyphicon-exclamation-sign').length) {
                        $($orderTypeTabList[orderTypeIndex]).append('<span class="glyphicon glyphicon-exclamation-sign"></span>');
                    }
                } else if ($($orderTypeTabList[orderTypeIndex]).find('.glyphicon-exclamation-sign').length) {
                    $($orderTypeTabList[orderTypeIndex]).find('.glyphicon-exclamation-sign').remove();
                }
            }

            // refreshing city tab tooltip info
            $currentCityTab.find('a').attr('title', $data_currentCityTab.find('a').attr('title'));

            // refreshing city tab count of orders
            $($('.city-tab')[cityTabIndex]).find('.theCount').text($($(data).find('.city-tab')[cityTabIndex]).find('.theCount').text());

            // refreshing city tab exclamation signs
            if ($data_currentCityTab.find('.glyphicon-exclamation-sign').length) {
                if (!$currentCityTab.find('.glyphicon-exclamation-sign').length) {
                    $currentCityTab.append('<span class="glyphicon glyphicon-exclamation-sign"></span>');
                }
            } else if ($currentCityTab.find('.glyphicon-exclamation-sign').length) {
                $currentCityTab.find('.glyphicon-exclamation-sign').remove();
            }
        }
        $(".city-tab a").tooltip({placement: 'bottom'});
    }
};
