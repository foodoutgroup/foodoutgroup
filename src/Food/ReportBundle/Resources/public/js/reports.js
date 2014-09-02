var Report = {
    bindEvents: function() {
        $.datepicker.setDefaults( $.datepicker.regional[ "lt" ] );
        $( ".date_pick" ).datepicker( {
            'numberOfMonths': 1
        });
    }
};
