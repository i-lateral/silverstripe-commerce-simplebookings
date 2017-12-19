<?php

class BookableProduct extends Product
{
    /**
     * @config
     */
    private static $description = "A bookable product that can be added to a booking";

    /**
     * A list of possible pricing periods for this product.
     * These periods are used to dertime how a product's#
     * price is calculted.
     * 
     * @var array
     * @config
     */
    private static $price_periods = array(
        86400 => "Day",
        43200 => "Half Day",
        3600 => "Hour"
    );

    /**
     * Define the default pricing period used. This should be
     * supported by the $price_periods defined above.
     * 
     * By default this is set to a day.
     * 
     * @var int
     * @config
     */
    private static $default_price_period = 86400;

    private static $db = array(
        "AvailablePlaces" => "Int",
        "MinimumPlaces" => "Int",
        "PricingPeriod" => "Int"
    );

    private static $has_many = array(
        'Resources' => 'BookingResource'
    );

    private static $defaults = array(
        "Stocked" => 0,
        "MinimumPlaces" => 0
    );

    private static $belongs_many_many = array(
        "Bookings" => "Booking"
    );

    public function populateDefaults()
    {
        $this->PricingPeriod = self::config()->default_price_period;
        parent::populateDefaults();
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Add spacing fields
        $availability_field = $fields->addFieldsToTab(
            "Root.Settings",
            array(
                DropdownField::create("PricingPeriod")
                    ->setSource($this->config()->price_periods)
                    ->setRightTitle(_t(
                        "SimpleBookings.PricingPeriodDescription",
                        "What time interval is this price for"
                    )),
                NumericField::create("AvailablePlaces")
                    ->setRightTitle(_t(
                        "SimpleBookings.AvailabilityDescription",
                        "The availability of this product for a given day"
                    )),
                NumericField::create("MinimumPlaces")
                    ->setRightTitle(_t(
                        "SimpleBookings.MinimumPlacesDescription",
                        "Does this require a minimum amount to book (use 0 to disable)?"
                    ))
            ),
            "StockLevel"
        );

        $fields->removeByName("Stocked");
        $fields->removeByName("StockLevel");
        $fields->removeByName("PackSize");
        $fields->removeByName("Weight"); 

        return $fields;
    }

    /**
     * Ensure that stockable settings are disabled on save.
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->Stocked = 0;
        $this->StockLevel = 0;
    }
}