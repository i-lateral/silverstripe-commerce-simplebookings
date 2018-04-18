<?php

use Guzzle\Service\Exception\ValidationException;

/**
 * A booked resource associated with a booking. A booking can contain multiple
 * resources that have a start and end date/time and can also be assocaited
 * with a product.
 * 
 * @category SilverstripeModule
 * @package  SimpleBookings
 * @author   ilateral <info@ilateral.co.uk>  
 * @license  https://spdx.org/licenses/BSD-3-Clause.html BSD-3-Clause
 * @link     https://github.com/i-lateral/silverstripe-commerce-simplebookings
 */
class BookingResource extends DataObject
{
    /**
     * The default time to increment the end date by. This is a string that
     * would be accepted by DateTime::modify().
     * 
     * @var string
     */
    private static $default_end = "+1 hour";

    private static $db = array(
        "BookedQTY" => "Int",
        "Start"     => "SS_Datetime",
        "End"       => "SS_Datetime"
    );

    private static $has_one = array(
        "Product"   => "BookableProduct",
        "Booking"   => 'Booking'
    );

    private static $summary_fields = array(
        "Start",
        "End",
        "Product.Title",
        "BookedQTY"
    );

    /**
     * Get the number of booked places this product has between the
     * start and end times.
     *
     * @param string $start Start date and time (preferably in standard DB format)
     * @param string $end   End date and time (preferably in standard DB format)
     * 
     * @return int
     */
    public function getBookedPlaces($start, $end)
    {
        return SimpleBookings::get_total_booked_spaces(
            $start,
            $end,
            $this->ProductID
        );
    }

    /**
     * Is this product available in the time frame set. We determine
     * this by finding how many places are currently booked in this
     * location 
     *
     * @param string $start Start date and time (preferably in standard DB format)
     * @param string $end   Start date and time (preferably in standard DB format)
     * @param int    $qty   amount of places you want to book between the two dates
     * 
     * @return boolean
     */
    public function isAvailable($start = null, $end = null, $qty = 0)
    {
        if (!$start && $this->Start) {
            $start = $this->Start;
        }
        if (!$end && $this->End) {
            $end = $this->End;
        }
        $places = $this->getPlacesRemaining($start, $end, $qty);

        if ($places - $qty <= 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * How many places are remaining for this product? If this is
     * negative then the product is overbooked
     *
     * @param string $start Start date and time (preferably in standard DB format)
     * @param string $end   Start date and time (preferably in standard DB format)
     * 
     * @return boolean
     */
    public function getPlacesRemaining($start = null, $end = null)
    {
        if (!$start && $this->Start) {
            $start = $this->Start;
        }
        if (!$end && $this->End) {
            $end = $this->End;
        }
        
        $booked_places = $this->getBookedPlaces($start, $end);
        
        return $this->Product()->AvailablePlaces - $booked_places;
    }

    /**
     * Get the number of places available for the associated product 
     * 
     * @return int
     */
    public function getAvailablePlaces()
    {
        if ($this->ProductID) {
            return $this->Product()->AvailablePlaces;
        }
        return 0;
    }

    /**
     * Update default date fields
     * 
     * {@inheritdoc}
     * 
     * @return FieldList
     */
    public function getCMSFields()
    {
        $self = $this;
        $this->beforeUpdateCMSFields(
            function ($fields) use ($self) {
                // Setup calendars on date fields
                $start_field = $fields->dataFieldByName("Start");
                $end_field = $fields->dataFieldByName("End");
                $product_field = $fields->dataFieldByName("ProductID");

                if (isset($product_field)) {
                    $fields->removeByName("ProductID");
                    $fields->addFieldToTab(
                        "Root.Main",
                        $product_field,
                        "BookedQTY"
                    );
                }

                if ($start_field && $end_field) {
                    $start_field
                        ->getDateField()
                        ->setConfig("showcalendar", true);
                    
                    $end_field
                        ->getDateField()
                        ->setConfig("showcalendar", true);
                }
            }
        );

        return parent::getCMSFields();
    }

    /**
     * Syncronise this resources booking (if available) on write and
     * ensure the the correct end date is set for the booking
     * 
     * @return void
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $booking = $this->Booking();
        
        if (isset($booking)) {
            $booking->updateEndDate();
            $booking->sync();
            $booking->write();
        }
    }
}