jQuery.noConflict();

(function($) {
    function checkDates($start,$end) 
    {
        $start_date = new Date($start);
        $end_date = new Date($end);
        $end_date.setDate($end_date.getDate()+1);
        $table = $('#Form_Form_Calendar');
        $dates = $table.find('td');
        $valid = true;
        $dates.each(function() {
            $curr_time = new Date($(this).attr('data-date')).getTime();
            if ($curr_time >= $start_date.getTime() && $curr_time <= ($end_date.getTime()-1) && $(this).hasClass('not-available')) {
                $valid = false;
            }
        });

        return $valid;
    }

    function deselectAllDates($start = true) {
        $table = $('#Form_Form_Calendar');
        $start_field = $('input[data-calendar=StartDate]');
        $end_field = $('input[data-calendar=EndDate]');
        if ($start) {
            $start_field.val('');
        }
        $end_field.val('');
        $dates = $table.find('td');
        $dates.each(function() {
            $(this).removeClass('selected');
            $(this).removeClass('hover');
            if (!$start && $(this).attr('data-date') == $start_field.val()) {
                $(this).addClass('selected');
            }
        });
    }
    
	$(document).ready(function() {
        $(document).on('change','#Form_Form #Quantity_Holder #Quantity',function() {
            $table = $('#Form_Form_Calendar');
            $dates = $table.find('td');
            $qty = $(this).val();

            $dates.each(function() {
                if ($(this).attr('data-lock') == false) {
                    $spaces = parseInt($(this).attr('data-spaces'));
                    if ($spaces < $qty) {
                        $(this).removeClass('available');
                        $(this).addClass('not-available');
                    } else {
                        $(this).removeClass('not-available');
                        $(this).addClass('available');  
                    }
                }
            });

            $start_field = $('input[data-calendar=StartDate]');
            $end_field = $('input[data-calendar=EndDate]');
            $start_date = $start_field.val();
            $end_date = $end_field.val();

            $valid = checkDates($start_date,$end_date);

            if (!$valid) {
                deselectAllDates();
            }

        });
    });
}(jQuery));