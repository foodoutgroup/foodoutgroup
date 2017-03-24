$(document).ready(function () {

    $(".slug").click(function () {

        var $element = $(this);
        var locale = $element.closest('.a2lix_translationsFields').prev().children('.active').children().text().split('[');
        locale = locale[0].replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '').toLowerCase();
        var slugTitle = $element.closest('.a2lix_translationsFields-' + locale).find('.slug_title').val();
        if (typeof slugTitle === 'undefined') {
            slugTitle = $('.slug_title').val();
        }
        var text = '';

        if ($element.val().length == 0 && slugTitle.length > 0) {

            $.ajax({
                type: 'GET',
                url: '/' + locale + '/ajax/change-slug-letters',
                async: false,
                data: {
                    text: slugTitle
                },
                success: function (data) {
                    text = data.replace(/[^a-z0-9\s]/gi, '-').replace(/[_\s]/g, '-')
                }
            });

            $element.val(text);
        }


    });


});