var Cart = {
    placeId: null,
    locale: null,

    bindEvents: function() {
        $('.delivery-type-radios input').on('ifChecked', function(event){
            Cart.deliveryTypeChanged($(this).val());
        });
    },

    /**
     * @var Object formObject
     */
    submitOrder: function(formObject) {
        var isPickup,
            deliveryAction,
            pickupAction,
            takeaway_not,
            takeaway_yep;

        if (Cart.isValidOrder(formObject)) {
            isPickup = $('.delivery-info-form input[name="delivery-type"]:checked').val() == 'pickup';
            deliveryAction = formObject.attr('data-delivery-action');
            pickupAction = formObject.attr('data-pickup-action');
            takeaway_not = $('.takeaway-not');
            takeaway_yep = $('.takeaway-yep');

            if (isPickup) {
                takeaway_not.find('input, select').prop('disabled', true);
                takeaway_yep.find('input, select').prop('disabled', false);
            }

            formObject.attr('action', isPickup ? pickupAction : deliveryAction)
                      .submit();
        } else {
            return false;
        }
    },

    /**
     * @var Object formObject
     */
    isValidOrder: function(formObject) {
        // TODO AJAX data to server, validate data and kill all humanity
        return true;
    },

    // deliveryTypeChanged: function(deliveryType) {
    //     $('.content-lefter-big').mask();
    //     switch (deliveryType) {
    //         case 'pickup':
    //             var url = Routing.generate('food_cart', { '_locale': Cart.locale, 'placeId': Cart.placeId, takeAway: 1 });
    //             break;

    //         default:
    //         case 'deliver':
    //             var url = Routing.generate('food_cart', { '_locale': Cart.locale, 'placeId': Cart.placeId });
    //             break;
    //     }

    //     window.location = url;
    // }

    deliveryTypeChanged: function(deliveryType) {
        var content_lefter, takeaway_not, takeaway_yep;

        takeaway_not = $('.takeaway-not');
        takeaway_yep = $('.takeaway-yep');
        content_lefter = $('.content-lefter-big');

        // get ready
        content_lefter.mask();

        // do
        switch (deliveryType) {
            case 'pickup':
                takeaway_not.hide();
                takeaway_yep.show();
                break;

            case 'deliver':
            default:
                takeaway_yep.hide();
                takeaway_not.show();
                break;
        }

        // cleanup
        content_lefter.unmask();
    }
};
