<?php

/**
 * Extension to the shopping cart that checks levels of availability when the
 * quantity is altered.
 *
 * @package SimpleBookings
 * @author  ilateral (http://ilateralweb.co.uk)
 */
class SimpleBookingShoppingCartExtension extends Extension
{
    /**
     * Calculate the item price, based on any bulk discounts set
     */
    public function onBeforeUpdate($item) 
    {
        $id = $item->StockID;
        $classname = $item->ClassName;
        $object = null;
        
        if ($id && $classname) {
            $object = $classname::get()->filter("StockID", $id)->first();
        }

        if ($object && $item->CustomisationArray) {
            $start_title = _t("SimpleBookings.StartDate", "Start Date"); 
            $end_title = _t("SimpleBookings.StartDate", "End Date");
            $start_date = null;
            $end_date = null;

            foreach ($item->CustomisationArray as $customisation) {
                if ($customisation["Title"] == $start_title) {
                    $start_date = $customisation["Value"];
                }

                if ($customisation["Title"] == $end_title) {
                    $end_date = $customisation["Value"];
                }
            }

            $total_places = SimpleBookings::get_total_booked_spaces($start_date, $end_date, $object->ID);

            // If we have asked for more places than are available set the quantity to the
            // nuymber of places left
            if ($total_places + $item->Quantity >= $object->AvailablePlaces) {
                $remaining = $object->AvailablePlaces - $total_places;
                $item->Quantity = $remaining;

                // Throw an exception to inform the user there are not enough places
                throw new Exception(
                    _t(
                        "SimpleBookings.NoSpacesAvailable",
                        "There are not enough spaces available for this date"
                    )
                );
            }
        }
    }
}