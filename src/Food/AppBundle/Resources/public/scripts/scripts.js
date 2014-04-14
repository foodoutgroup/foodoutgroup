(function($, window){
    $(function() {
        var $window = $(window);

        /*Placeholder for old browsers*/
        $('input[placeholder], textarea[placeholder]').placeholder();

        /*JS PIE. Fetures and usage: http://css3pie.com/documentation/supported-css3-features/*/
        if (window.PIE) {
            $('.rounded').each(function() {
                PIE.attach(this);
            });
        }

        resizeSensitive(); 

        $window.resize(function () {
            resizeSensitive();
        });

        // Boxer lightbox plugin
        
        $('.custom-select').selectmenu();

        $("input").iCheck();


        $(".boxer").boxer({
            callback: function(){
                $("input").iCheck();
            }
        });


        $( "#detailed-restaurant" ).tabs({
            activate: function( event, ui ) {
                ui.newTab.trigger('tab-activate');
            }
        });


        $('.restoran-rating').raty({
            readOnly: true,
            score: function() {
                return $(this).attr('data-stars');
            },
            path: '/bundles/foodapp/images/'
        });

        function resizeSensitive() {
            
        }
    });
})(jQuery, window);

$(document).ready(function() {
    $('body').on('submit', '.righter.register-form', function(e) {
        var form = $(this);
        var url =  form.attr('action');
        var success_url = form.attr('data-success-url');
        var data = form.serialize();
        var callback = function(response) {
            if (response.length > 0) {
                $('.registration_form_wrapper:visible').html(response);
            } else {
                top.location.href = success_url;
            }
        }

        $.post(url, data, callback);

        return false;
    });
});
