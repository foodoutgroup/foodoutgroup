var Cart = {
    placeId: null,
    locale: null,

    bindEvents: function() {
        $('.delivery-type-radios label').bind('click', function(){
            Cart.deliveryTypeChanged($(this).find('input').val());
        });
    },

    /**
     * @var Object formObject
     */
    submitOrder: function(formObject) {
        if (Cart.isValidOrder(formObject)) {
            console.log('I are valid, submit');
            formObject.submit();
        } else {
            console.log('I are idiot, do nothing');
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

    deliveryTypeChanged: function(deliveryType) {
        $('.content-lefter-big').mask();
        switch (deliveryType) {
            case 'pickup':
                var url = Routing.generate('food_cart', { '_locale': Cart.locale, 'placeId': Cart.placeId, takeAway: 1 });
                break;

            default:
            case 'deliver':
                var url = Routing.generate('food_cart', { '_locale': Cart.locale, 'placeId': Cart.placeId });
                break;
        }

        window.location = url;
    }
};