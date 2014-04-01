var Dispatcher = {
    _locale: 'lt',

    setLocale: function(locale) {
        Dispatcher._locale = locale;
    },

    onLoadEvents: function() {
        $('.city_list').tabs();

        // Visus stripintus tekstus ispiesim tooltipe :)
        $(".spliced-text").tooltip({});

        $(".order_checkbox").bind('click', function(){
            Dispatcher.toggleDriverButton($(this));
        });

        $(".change_status_button").bind('click', function() {
            Dispatcher.showStatusPopup($(this));
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
                    resizable: false,
                    modal: true,
                    buttons: {
                        // TODO isversti
                        "Keisti": function() {
                            $( this ).dialog( "close" );
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
        var activeList = checkbox.parent().find(".order_list");
        var checkedBoxes = activeList.find('.order_checkbox:checked');
    }
};