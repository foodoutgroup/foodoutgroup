function alertBox(title_msg, content_msg, okText) {
    $("<div></div>").html(content_msg).dialog({
        title: title_msg,
        resizable: false,
        modal: true,
        buttons: {
            "Ok": {
                'text': okText,
                'click': function() {
                    $( this ).dialog( "close" );
                }
            }
        }
    });
};