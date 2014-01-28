$(document).ready(function(){
    $('.container').delegate('.dish .add-cart', 'click', function(event){
        alert('{{ a }}');
        event.stopPropagation();
        event.preventDefault();
        return false;
    });
});