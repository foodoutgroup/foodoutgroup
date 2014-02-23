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


        $( "#detailed-restaurant" ).tabs();


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