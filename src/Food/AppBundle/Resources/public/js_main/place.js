$(document).ready(function() {
    Place.bindListTypeButtons();
});

var Place = {
    listTypeSaveUrl: '',

    bindListTypeButtons: function() {
        var viewTypeHolder = $('.menu-righter .view-type');

        viewTypeHolder.find('.ico-thumbs').bind('click', function() {
            Place.switchMenuLayout('thumbs');

            return false;
        });
        viewTypeHolder.find('.ico-list').bind('click', function() {
            Place.switchMenuLayout('list');

            return false;
        });
    },
    switchMenuLayout: function(type) {
        var viewTypeHolder = $('.menu-righter .view-type');
        var dishesMenu = $('.restaurant-menu');

        if (Place.listTypeSaveUrl != '') {
            $.ajax({
                url: Place.listTypeSaveUrl+'/'+type
            });
        }

        if (type == 'thumbs') {
            viewTypeHolder.find('.ico-thumbs').addClass('active');
            viewTypeHolder.find('.ico-list').removeClass('active');

            dishesMenu.removeClass('display-list');
            dishesMenu.find('.dish_larger').removeClass('hidden');
            dishesMenu.find('.dish_smaller').addClass('hidden');
        } else {
            viewTypeHolder.find('.ico-thumbs').removeClass('active');
            viewTypeHolder.find('.ico-list').addClass('active');

            dishesMenu.addClass('display-list');
            dishesMenu.find('.dish_larger').addClass('hidden');
            dishesMenu.find('.dish_smaller').removeClass('hidden');
        }
    }
}