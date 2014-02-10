var Cart = {
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
    }
};