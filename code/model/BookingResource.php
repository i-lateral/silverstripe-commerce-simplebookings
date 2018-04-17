<?php

class BookingResource extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar',
        "BookedQTY" => "Int",
        "Start" => "SS_Datetime",
        "End"   => "SS_Datetime"
    );

    private static $has_one = array(
        "Product" => "BookableProduct",
        "Booking" => 'Booking'
    );

    /**
     * Get the number of booked places this product has between the
     * start and end times.
     *
     * @param  string $start Start date and time (preferably in standard DB format)
     * @param  string $end   End date and time (preferably in standard DB format)
     * @return Int
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
     * @param  string $start Start date and time (preferably in standard DB format)
     * @param  string $end   Start date and time (preferably in standard DB format)
     * @param  int    $qty   amount of places you want to book between the two dates
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
        $places = $this->PlacesRemaining($start, $end, $qty);

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
     * @param  string $start Start date and time (preferably in standard DB format)
     * @param  string $end   Start date and time (preferably in standard DB format)
     * @return boolean
     */
    public function PlacesRemaining($start = null, $end = null)
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

    public function getAvailablePlaces()
    {
        if ($this->ProductID) {
            return $this->Product()->AvailablePlaces;
        }
        return 0;
    }
}