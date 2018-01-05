<?php

class CalendarDatePickerField extends FormField
{
    private static $allowed_actions = array(
        'calendar'
    );

    private static $url_handlers = array(
        'calendar/$Month/$Year' => 'calendar'
    );

    protected $start_field;
    protected $end_field;

    protected $day_headings = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ];

    public function getStartField()
    {
        return $this->start_field;
    }

    public function setStartField(HiddenField $field) {
        $this->start_field = $field;
    }

    public function getEndField()
    {
        return $this->end_field;
    }

    public function setEndField(HiddenField $field) {
        $this->end_field = $field;
    }

    /* draws a calendar */
    function calendar() {

        $month = $this->request->params('Month');
        if (!$month) {
            $month = date('m');
        }
        $year = $this->request->params('Year');
        if (!$year) {
            $year = date('Y');
        }

        /* draw table */
        $calendar = ArrayList::create();

        /* days in month */
        $days = ArrayList::create();

        /* days and weeks vars now ... */
        $running_day = date('w',mktime(0,0,0,$month,1,$year));
        $days_in_month = date('t',mktime(0,0,0,$month,1,$year));
        $days_in_this_week = 1;
        $day_counter = 0;
        $dates_array = array();

        /* print "blank" days until the first of the current week */
        for($x = 0; $x < $running_day; $x++):
            $day = ArrayData::create([
                'InMonth' => false,
                'Number' => ''
            ]);
            $days->push($day);
            $days_in_this_week++;
        endfor;

        /* keep going with days.... */
        for($list_day = 1; $list_day <= $days_in_month; $list_day++):
            $day = ArrayData::create([
                'InMonth' => true,
                'Number' => $list_day
            ]);
            $days->push($day);
                
            if($running_day == 6):
                $running_day = -1;
                $days_in_this_week = 0;
            endif;
            $days_in_this_week++; $running_day++; $day_counter++;
        endfor;

        /* finish the rest of the days in the week */
        if($days_in_this_week < 8):
            for($x = 1; $x <= (8 - $days_in_this_week); $x++):
                $day = ArrayData::create([
                    'InMonth' => false,
                    'Number' => ''
                ]);
                $days->push($day);
            endfor;
        endif;;

        $back = $this->getBackLink();
        $next = $this->getNextLink();
        $month = $this->getMonthField();
        $year = $this->getYearField();
        $headings = ArrayList::create();
        
        foreach ($this->day_headings as $heading) {
            $headings->push(ArrayData::create([
                'Day' => $heading
            ]));
        }

        $this->extend('updateCalendar',$days);

        return $this->renderWith(
            'CalendarTable',
            [
                'DayHeadings' => $headings,
                'BackLink' => $back,
                'NextLink' => $next,
                'MonthField' => $month,
                'YearField' => $year,
                'Days' => $days
            ]
        );
    }

    public function Field($properties = array())
    {
        return $this->calendar();
    }

    public function getBackLink()
    {
        return 'Back';
    }

    public function getNextLink()
    {
        return 'Next';        
    }

    public function getMonthField()
    {
        return 'Month';
    }

    public function getYearField()
    {
        return 'Year';
    }

}