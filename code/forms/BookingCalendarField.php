<?php

class BookingCalendarField extends CalendarField
{
    protected $product;

    /**
     * define the time format
     *
     * @config
     * 
     * @var string
     */
    private static $time_format = "H:i:s";

    /**
     * define the date format
     *
     * @config
     * 
     * @var string
     */
    private static $date_format = "Y-m-d";

    /**
     * define the full datetime format
     *
     * @config
     * 
     * @var string
     */
    private static $datetime_format = "Y-m-d H:i:s";

    protected $options = [
        'day_format' => 'D',
        'month_format' => 'M',
        'year_format' => 'Y',
        'allow_past_dates' => false,
        'future_limit' => 10,
        'past_limit' => 0,
        'days_count' => 0,
        'StartName' => 'StartDate',
        'EndName' => 'EndDate',
        'useEndField' => true
    ];

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct($product) 
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Create a new file field.
     *
     * @param string $name  The internal field name, passed to forms.
     * @param string $title The field label.
     * @param int    $value The value of the field.
     */
    public function __construct($name, $title = null, $value = null,$product) 
    {
        $this->product = $product;
        
        parent::__construct($name, $title, $value);
    }

    public function getCalendarDays($month,$year)
    {
        $today = new Date();
        $today->setValue(date($this->config()->datetime_format));
        /* days in month */
        $days = parent::getCalendarDays($month, $year);

        $product = $this->getProduct();

        if ($product && method_exists($product, 'getPlacesRemaining')) {
            foreach ($days as $day) {
                $start = new SS_DateTime();
                $start->setValue($day->Date->format($this->config()->date_format." 00:00:00"));
                $end = new SS_DateTime();
                $end->setValue($day->Date->format($this->config()->date_format." 23:59:59"));

                $spaces = $product->getPlacesRemaining($start->format($this->config()->datetime_format), $end->format($this->config()->datetime_format));
                if (($spaces > 0 && $day->Date->format($this->config()->datetime_format) > $today->format($this->config()->datetime_format)) 
                    && !in_array($day->Date->format($this->config()->date_format), $this->disabled_dates)
                ) {
                    $day->Availability = 'available';
                    $day->Spaces = $spaces;
                    $day->Lock = false;
                } else {
                    $day->Availability = 'not-available'; 
                    $day->Spaces = 0;
                    $day->Lock = true;                   
                }
            }
        }

        return $days;
    }
}