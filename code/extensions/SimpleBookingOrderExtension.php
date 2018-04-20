<?php

use ilateral\SimpleBookings\Helpers\Syncroniser;

class SimpleBookingOrderExtension extends DataExtension
{

    /**
     * Find a booking associated with this order (if it exists)
     *
     * @return Booking
     */
    public function Booking()
    {
        return Booking::get()->find("OrderID", $this->owner->ID);
    }

    /**
     * Syncronise this order with a booking object
     * 
     * @return void
     */
    public function syncWithBooking()
    {
        // Either use existing booking (or generate a new one.
        $booking = $this->owner->Booking();

        if (!$booking) {
            $booking = Booking::create();
        }

        $sync = Syncroniser::create($booking, $this->owner);

        if ($this->owner->isChanged("Status") && $this->owner->isPaid()) {
            $sync->setSyncProducts(true);
        }

        // Pass data to syncroniser. If this booking is new
        // and there is nothing to sync, it will not be written
        $sync->orderToBooking();

        $this->owner->extend("afterSyncWithBooking", $booking, $sync);
    }

    /**
     * Create a Booking when the order is marked as paid
     * 
     * @return void
     */
    public function onAfterWrite()
    {
        $this->syncWithBooking();
    }
}