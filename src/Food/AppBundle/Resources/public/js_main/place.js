$(document).ready(function() {
    Place.bindListTypeButtons();
});

var Place = {
    bindListTypeButtons: function() {
        var viewTypeHolder = $('.menu-righter .view-type');

        viewTypeHolder.find('.ico-thumbs').bind('click', function() {
            var form = $('<form method="post">' +
                '<input type="hidden" name="view-type" value="thumbs" />' +
                '</form>');
            $('body').append(form);

            form.submit();

            return false;
        });
        viewTypeHolder.find('.ico-list').bind('click', function() {
            var form = $('<form method="post">' +
                '<input type="hidden" name="view-type" value="list" />' +
                '</form>');
            $('body').append(form);

            form.submit();

            return false;
        });
    }
}