<?php

class BookableProduct extends Product
{
    /**
     * @config
     */
    private static $description = "A bookable product that can be added to a booking";

    private static $db = array(
        "AvailablePlaces" => "Int"
    );

    private static $defaults = array(
        "Stocked" => 0
    );

    private static $belongs_many_many = array(
        "Bookings" => "Booking"
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Add right title to stock level
        $availability_field = $fields->addFieldToTab(
            "Root.Settings",
            NumericField::create("AvailablePlaces")
                ->setRightTitle(_t(
                    "SimpleBookings.AvailabilityDescription",
                    "The availability of this product for a given day"
                )),
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