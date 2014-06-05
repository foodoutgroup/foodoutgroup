$(document).ready(function(){
    $('.container').delegate('.dish .add-cart', 'click', function(event){
        event.stopPropagation();
        event.preventDefault();
        return false;
    });
});