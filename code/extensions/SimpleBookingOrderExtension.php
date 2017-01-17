<?php

class SimpleBookingOrderExtension extends DataExtension
{
    private static $has_one = array(
        "Booking" => "Booking"
    );

    /**
     * Create a Booking when the order is marked as paid
     *
     */
    public function onBeforeWrite()
    {   
        if ($this->owner->isChanged("Status") && $this->owner->Status == $this->owner->config()->completion_status && !$this->owner->BookingID) {
            $products = ArrayList::create();
            $start_title = _t("SimpleBookings.StartDate", "Start Date"); 
            $end_title = _t("SimpleBookings.EndDate", "End Date");
            $start_date = null;
            $end_date = null;
            $title = null;

            // First see if this order contains bookable products
            foreach ($this->owner->Items() as $item) {
                $product = $item->Match();

                if ($product && $product instanceof BookableProduct) {
                    if (!$title) {
                        $title = $item->Title;
                    }

                    foreach ($item->Customisations() as $customisation) {
                        if ($customisation->Title == $start_title) {
                            $start_date = $customisation->Value;
                        }

                        if ($customisation->Title == $end_title) {
                            $end_date = $customisation->Value;
                        }
                    }

                    $products->add(ArrayData::create(array(
                        "Product" => $product,
                        "Quantity" => $item->Quantity
                    )));
                    
                }
            }

            // If we have found bookable products
            if ($products->exists()) {
                $booking = Booking::create();
                $booking->Title = $title;
                $booking->Start = $start_date;
                $booking->End = $end_date;
                $booking->OrderID = $this->owner->ID;
                $booking->write();

                foreach ($products as $product) {
                    $booking->Products()->add(
                        $product->Product,
                        array("BookedQTY", $product->Quantity)
                    );
                }

                $this->owner->BookingID = $booking->ID;
            }
        }
    }
}