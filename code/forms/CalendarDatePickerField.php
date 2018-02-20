<?php

class CalendarDatePickerField extends FormField
{
    private static $allowed_actions = array(
        'calendar'
    );

    private static $url_handlers = array(
        'calendar//$Month/$Year' => 'calendar'
    );

    protected $start_field;
    protected $end_field;
    protected $product;

    protected $options = [
        'day_format' => 'l',
        'month_format' => 'M',
        'year_format' => 'Y',
        'allow_past_dates' => false,
        'future_limit' => 10,
        'past_limit' => 0,
        'days_count' => 3
    ];

	/**
	 * Create a new file field.
	 *
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param int $value The value of the field.
	 */
	public function __construct($name, $title = null, $value = null, $product) {
        $this->product = $product;
        
		parent::__construct($name, $title, $value);
    }
    
    public function getStartField()
    {
        return $this->start_field;
    }

    public function setStartField(HiddenField $field) {
        $this->start_field = $field;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options) {
        $this->options = array_merge($this->options, $options);
    }

    public function getEndField()
    {
        return $this->end_field;
    }

    public function setEndField(HiddenField $field) {
        $this->end_field = $field;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct($product) {
        $this->product = $product;
    }

    public function getMonth() {
        $month = $this->getRequest()->param('Month');
        if ($month) {
            return $month;
        }
        return date('n');
    }

    public function getYear() {
        $year = $this->getRequest()->param('Year');
        if ($year) {
            return $year;
        }
        return date('Y');
    }

    /* draws a calendar */
    function calendar() {
        $today = new Date();
        $today->setValue(date("Y-m-d H:i:s"));
        
        $month = $this->getMonth();
        $year = $this->getYear();

        /* draw table */
        $calendar = ArrayList::create();

        /* days in month */
        $days = ArrayList::create();

        /* days and weeks vars now ... */
        $running_day = date('w',mktime(0,0,0,$month,1,$year));
        $days_in_month = date('t',mktime(0,0,0,$month,1,$year));
        $days_last_month = date('t',mktime(0,0,0,($month - 1),1,$year));
        $days_in_this_week = 1;
        $day_counter = 0;
        $dates_array = array();

        /* print "blank" days until the first of the current week */
        for($x = 0; $x < $running_day; $x++) {
            $datetime = new DateTime(date('d/m/Y',mktime(0,0,0,1,$month,$year)));
            $datetime->sub(date_interval_create_from_date_string(($running_day - $x).' days'));
            $date = new Date();
            $date->setValue(date('d/m/Y',mktime(0,0,0,($month-1),($days_last_month - ($running_day-$x) + 1),$year)));
            $day = ArrayData::create([
                'InMonth' => false,
                'Number' => $datetime->format('d'),
                'Date' => $date
            ]);
            $days->push($day);
            $days_in_this_week++;
        }

        /* keep going with days.... */
        for($list_day = 1; $list_day <= $days_in_month; $list_day++) {
            $date = new Date();
            $date->setValue(date('d/m/Y',mktime(0,0,0,$month,$list_day,$year)));
            $day = ArrayData::create([
                'InMonth' => true,
                'Number' => $list_day,
                'Date' => $date
            ]);

            $days->push($day);
                
            if($running_day == 6) {
                $running_day = -1;
                $days_in_this_week = 0;
            }
            $days_in_this_week++; $running_day++; $day_counter++;
        }

        /* finish the rest of the days in the week */
        if($days_in_this_week < 8) {
            for($x = 1; $x <= (8 - $days_in_this_week); $x++) {
                $date = new Date();
                $date->setValue(date('d/m/Y',mktime(0,0,0,($month + 1),$x,$year)));
                $day = ArrayData::create([
                    'InMonth' => false,
                    'Number' => $x,
                    'Date' => $date
                ]);
                $days->push($day);
            }
        }
        
        
        $product = $this->getProduct();

        if ($product) {
            foreach ($days as $day) {
                $day->Spaces = $product->AvailablePlaces - $product->getBookedPlaces($day->Date->format("Y-m-d 00:00:00"), $day->Date->format("Y-m-d 23:59:59"));
                if ($day->Spaces > 0 && $day->Date->format("Y-m-d H:i:s") > $today->format("Y-m-d H:i:s")) {
                    $day->Availability = 'available';
                } else {
                    $day->Availability = 'not-available';                    
                }
            }
        }

        $back = $this->getBackLink();
        $next = $this->getNextLink();
        $month = $this->getMonthField();
        $year = $this->getYearField();
        $headings = ArrayList::create();
        
        foreach ($this->getDaysOfWeek() as $heading) {
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
                'Days' => $days,
                'StartField' => $this->getStartField(),
                'EndField' => $this->getEndField()
            ]
        );
    }

    public function Field($properties = array())
    {
        return $this->calendar();
    }

    public function getDaysOfWeek()
    {
        $days = [];
        for ($d=0; $d<7; $d++) {
            $day = date($this->options['day_format'], mktime(0,0,0,1, $d, date('Y')));
            $days[] = $day;
        }
        
        return $days;
    }

    public function getBackLink()
    {
        $month = $this->getMonth();
        $year = $this->getYear();

        $date = new DateTime(date('d/m/Y',mktime(0,0,0,1,$month,$year)));
        $date->sub(date_interval_create_from_date_string('1 month'));

        return Controller::join_links(
            $this->Link('calendar'),
            $date->format('n'),
            $date->format('Y')
        );
    }

    public function getNextLink()
    {
        $month = $this->getMonth();
        $year = $this->getYear();

        $date = new DateTime(date('d/m/Y',mktime(0,0,0,1,$month,$year)));
        $date->add(date_interval_create_from_date_string('1 month'));

        return Controller::join_links(
            $this->Link('calendar'),
            $date->format('n'),
            $date->format('Y')
        );       
    }

    public function getMonthField()
    {
        $current_month = $this->getMonth();
        $months = [];

        for ($m=1; $m<=12; $m++) {
            $month = date($this->options['month_format'], mktime(0,0,0,$m, 1, date('Y')));
            $months[$m] = $month;
        }

        return DropdownField::create(
            'CalendarMonth',
            'Month',
            $months
        )->setValue($current_month);
    }

    public function getYearField()
    {
        $this_year = date('Y');
        $current_year = $this->getYear();
        $latest_year = $this_year + $this->options['future_limit'];

        if ($this->options['allow_past_dates']) {
            $earliest_year = $this_year - $this->options['past_limit'];
        } else {
            $earliest_year = $this_year;
        }

        $years = [];

        foreach ( range( $latest_year, $earliest_year ) as $i ) {
            $years[$i] = date($this->options['year_format'], mktime(0,0,0,1, 1, $i));
        }

        return DropdownField::create(
            'CalendarYear',
            'Year',
            $years
        )->setValue($current_year);
    }

    	/**
	 * Allows customization through an 'updateAttributes' hook on the base class.
	 * Existing attributes are passed in as the first argument and can be manipulated,
	 * but any attributes added through a subclass implementation won't be included.
	 *
	 * @return array
	 */
	public function getAttributes() {
		$attributes = array(
			'name' => $this->getName(),
			'value' => $this->Value(),
			'class' => $this->extraClass(),
			'id' => $this->ID(),
			'disabled' => $this->isDisabled(),
            'readonly' => $this->isReadonly(),
            'data-url' => $this->Link('calendar'),
            'data-days' => $this->options['days_count']
		);

		if($this->Required()) {
			$attributes['required'] = 'required';
			$attributes['aria-required'] = 'true';
		}

		$attributes = array_merge($attributes, $this->attributes);

		$this->extend('updateAttributes', $attributes);

		return $attributes;
	}
}