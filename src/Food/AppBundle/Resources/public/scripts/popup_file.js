function add_mail(element) {
    var checked = $("#valentines_agree").parent('[class*="icheck"]').hasClass("checked");
    var email = $('#valentines_mail').val();
    var url = $(element).attr('data-url');
    if (checked && email.length > 0) {

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                email: email
            },
            success: function (data) {
                if (data.success) {
                    $.fancybox.close($('.modal-valentines-div'));
                }
            }
        });
    }

}

$(document).ready(function () {

    var valentines = $('.modal-valentines-div');
    var mobile = $('.modal-mobile-div');
    var easter = $('.modal-easter-div');
    var element;

    if (valentines.length) {
        element = valentines
    } else if (mobile.length) {
        element = mobile;
    } else if (easter.length) {
        element = easter;
    } else {
        element = '';
    }

    if (element.length > 0) {

        setTimeout(function () {
            $.fancybox.open(element);

        }, 5000);
    }
});
