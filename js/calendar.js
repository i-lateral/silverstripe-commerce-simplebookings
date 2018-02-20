jQuery.noConflict();

(function($) {
    function calendarAjax($url = null) 
    {
        $table = $('#Form_Form_Calendar');
        $start_field = $('#CalendarStart input');
        $end_field = $('#CalendarEnd input');
        $start_date = $start_field.val();
        $end_date = $end_field.val();
        $link = $table.attr('data-url');
        $month_field = $table.find('#CalendarMonth');
        $year_field = $table.find('#CalendarYear');
        $month = $month_field.val();
        $year = $year_field.val();
        if ($url == null) {
            $url = $link+'/'+$month+'/'+$year;
        }
        $table.append('<div class="preloader-holder"><div class="preloader"><span class="preloader-icon">&#10227;</span></div></div>');
        $.get($url,function($data) {
            $table.replaceWith($data);
            $start_field = $('#CalendarStart input');
            $end_field = $('#CalendarEnd input');
            $start_field.val($start_date);
            $end_field.val($end_date);
            if ($start_field.val() && $end_field.val()) {
                selectDates($start_field.val(),$end_field.val());
            }
        });
    }

    function selectDates($start,$end) 
    {
        $start_date = new Date($start);
        $end_date = new Date($end);
        $end_date.setDate($end_date.getDate()+1);
        $table = $('#Form_Form_Calendar');
        $dates = $table.find('td');
        $dates.each(function() {
            $curr_time = new Date($(this).attr('data-date')).getTime();
            if ($curr_time >= $start_date.getTime() && $curr_time <= ($end_date.getTime()-1)) {
                $(this).addClass('selected');
            } else {
                $(this).removeClass('selected');
            }
        });
    }

    function hoverDates($start,$end) 
    {
        $start_date = new Date($start);
        $end_date = new Date($end);
        $end_date.setDate($end_date.getDate()+1)
        $table = $('#Form_Form_Calendar');
        $dates = $table.find('td');
        $dates.each(function() {
            $curr_time = new Date($(this).attr('data-date')).getTime();
            if ($curr_time >= $start_date.getTime() && $curr_time <= ($end_date.getTime()-1)) {
                $(this).addClass('hover');
            } else {
                $(this).removeClass('remove');
            }
        });
    }

    function deselectAllDates() {
        $table = $('#Form_Form_Calendar');
        $dates = $table.find('td');
        $dates.each(function() {
            $(this).removeClass('selected');
        });
    }

    function removeHover() {
        $table = $('#Form_Form_Calendar');
        $dates = $table.find('td');
        $dates.each(function() {
            $(this).removeClass('hover');
        });
    }

	$(document).ready(function() {
        $(document).on('change','#Form_Form_Calendar select',function() {
            calendarAjax();
        });

        $(document).on('click','#Form_Form_Calendar .direction-link',function(e) {
            e.preventDefault();
            calendarAjax($(this).attr('href'));
        });

        $(document).on('mouseenter','#Form_Form_Calendar .available',function() {
            $table = $('#Form_Form_Calendar');
            $days = $table.attr('data-days');
            if ($days > 0) {
                removeHover();
                $curr_date = new Date($(this).attr('data-date'));
                $next_date = new Date($(this).attr('data-date'));
                $next_date.setDate($curr_date.getDate() + (parseInt($days) - 1));
                $date_string = $next_date.getFullYear() + '-' + (parseInt($next_date.getMonth()) + 1) + '-' + $next_date.getDate();
                hoverDates($(this).attr('data-date'),$date_string); 
            }
        });

        $(document).on('mouseleave','#Form_Form_Calendar .calendar-row',function() {
            $table = $('#Form_Form_Calendar');
            $days = $table.attr('data-days');
            if ($days > 0) {
                removeHover();
            }
        });

        $(document).on('click','#Form_Form_Calendar .available',function() {
            $table = $('#Form_Form_Calendar');
            $days = $table.attr('data-days');
            $start_field = $('#CalendarStart input');
            $end_field = $('#CalendarEnd input');
            if ($start_field.length > 0 && $days > 0 && $end_field.length > 0) {
                $start_field.val($(this).attr('data-date'));
                $next_date = new Date($(this).attr('data-date'));
                $next_date.setDate($next_date.getDate()+(parseInt($days)-1));
                $date_string = $next_date.getFullYear()+'-'+($next_date.getMonth()+1)+'-'+$next_date.getDate();
                $end_field.val($date_string);
                selectDates($start_field.val(),$end_field.val()); 
            } else if ($start_field.length > 0 && !$start_field.val()) {
                $start_field.val($(this).attr('data-date'));
                $(this).addClass('selected');
            } else if ($end_field.length > 0 && !$end_field.val()) {
                if (new Date($(this).attr('data-date')).getTime() > new Date($start_field.val()).getTime()) {
                    $end_field.val($(this).attr('data-date'));
                } else {
                    $end_field.val($start_field.val());
                    $start_field.val($(this).attr('data-date'));
                }
                selectDates($start_field.val(),$end_field.val());            
            } else {
                if (new Date($(this).attr('data-date')).getTime() < new Date($start_field.val()).getTime()) {
                    $start_field.val($(this).attr('data-date'));
                } else if (new Date($(this).attr('data-date')).getTime() > new Date($end_field.val()).getTime()) {
                    $end_field.val($(this).attr('data-date'));
                } else {
                    $start_field.val('');
                    $end_field.val('');
                }
                if ($start_field.val() && $end_field.val()) {
                    selectDates($start_field.val(),$end_field.val()); 
                } else {
                    deselectAllDates();
                }           
            }
        });
    });
}(jQuery));