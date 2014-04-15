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

$(function() {
  return $('body').on('submit', '.righter.register-form', function(e) {
    var callback, data, form, success_url, url;

    form = $(this);
    url = form.attr('action');
    success_url = form.attr('data-success-url');
    data = form.serialize();

    callback = function(response) {
      if (response.length > 0) {
        return $('.registration_form_wrapper:visible').html(response);
      } else {
        return top.location.href = success_url;
      }
    };

    $.post(url, data, callback);

    return false;
  });
});
