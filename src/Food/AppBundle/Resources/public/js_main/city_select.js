$(document).ready(function(){
    $('.city_select').selectmenu();

    if ($('.neighbourhood_select').length > 0) {
        $('.neighbourhood_select').chosen({
            width: "100%",
            no_results_text: no_results_text
        });
        var index_neighbourhood = $('.neighbourhood_select:enabled');

        var showNeighbourhood = function(selectedCity){
            var opt_cnt = true;
            index_neighbourhood.find('option').hide();
            $selectedOption = index_neighbourhood.find('option:selected');
            index_neighbourhood.find('option').filter(function(){
                if ($(this).data('parentcityid') == selectedCity) {
                    if (opt_cnt && selectedCity != $selectedOption.data('parentcityid')) {
                        $(this).prop('selected', 'selected');
                        opt_cnt = false;
                    }
                    return true;
                }
                return false;
            }).show();
            index_neighbourhood.filter(':enabled').trigger('chosen:updated');
        };

        var cityid = $('.city_select').find('option:selected').data('cityid');
        showNeighbourhood(cityid);

        $('.city_select').change(function(){
            var cityid = $(this).find('option:selected').data('cityid');
            showNeighbourhood(cityid);
        });
    }

    // Chosen touch support.
    if ($('.chosen-container').length > 0) {
        $('.chosen-container').on('touchstart', function(e){
            e.stopPropagation(); e.preventDefault();
            $(this).trigger('mousedown');
        });
    }
});
