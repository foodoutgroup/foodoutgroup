jQuery(document).ready(function(){
    jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "" ] );
    //jQuery(".datepicker").datepicker( jQuery.datepicker.regional[ "lt" ]);
    jQuery(".datepicker").datepicker({
        numberOfMonths: 3
    });
});