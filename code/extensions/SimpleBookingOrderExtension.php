<?php

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
            $booking = $this->owner->Booking();

            // First see if this order contains bookable products
            foreach ($this->owner->Items() as $item) {
                $product = $item->FindStockItem();

                if ($product && $product instanceof BookableProduct) {
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
            if (!$booking && $products->exists()) {
                $booking = Booking::create();
                $booking->Start = $start_date;
                $booking->End = $end_date;
                $booking->OrderID = $this->owner->ID;
                $booking->write();

                foreach ($products as $product) {
                    $resource = BookingResource::create();
                    $resource->Title = $product->Product->Title;
                    $resource->Start = $start_date;
                    $resource->End = $end_date;
                    $resource->ProductID = $product->Product->ID;
                    $resource->BookedQTY = $product->Quantity;
                    $resource->write();

                    $booking->Resources()->add($resource);
                }

                $this->owner->BookingID = $booking->ID;
            }
        }
    }
}