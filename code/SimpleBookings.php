<?php

/**
 * Config class for this module to hold global settings.
 *
 * @package SimpleBookings
 * @author ilateral (http://ilateralweb.co.uk)
 */
class SimpleBookings extends ViewableData
{

    /**
     * Do BookableProducts lock the shopping cart (so
     * they cannot be edited)?
     *
     * @var Boolean
     * @config 
     */
    private static $lock_cart = true;

    /**
     * Do BookableProducts contain a deliverable component
     * (for example tickets to be posted). By default this
     * module assumes no.
     *
     * @var Boolean
     * @config 
     */
    private static $allow_delivery = false;

    /**
     * Find the total spaces already booked between the two provided dates.
     *
     * @param date_from The starting date
     * @param date_to The end date
     * @param ID The ID of the product we are trying to book
     * @return Int
     * @return array
     */
    public static function get_total_booked_spaces($date_from, $date_to, $ID)
    {
        // First get a list of days between the start and end date
        $days = self::create_date_range_array($date_from, $date_to);
        $total_places = 0;
        $product = BookableProduct::get()->byID($ID);

        if ($product) {
            // Get all bookings with a start date within
            // the date range
            $bookings_start = Booking::get()->filter(array(
                "Start:LessThanOrEqual" => $date_to,
                "Start:GreaterThanOrEqual" => $date_from,
                "Products.ID" => $ID
            ));

            // Get all bookings with an end date within
            // the date range
            $bookings_end = Booking::get()->filter(array(
                "End:LessThanOrEqual" => $date_to,
                "End:GreaterThanOrEqual" => $date_from,
                "Products.ID" => $ID
            ));

            // Create a new list of all bookings and clean it
            // of duplicates
            $all_bookings = ArrayList::create();
            $all_bookings->merge($bookings_start);
            $all_bookings->merge($bookings_end);
            $all_bookings->removeDuplicates();
            
            // Now get all products inside these bookings that
            // match our date range and tally the results
            foreach ($all_bookings as $booking) {
                foreach ($booking->Products() as $match_product) {
                    $start_stamp = strtotime($date_from);
                    $end_stamp = strtotime($date_to);
                    $prod_start_stamp = strtotime($match_product->Start);
                    $prod_end_stamp = strtotime($match_product->End);

                    if (
                        $prod_start_stamp >= $start_stamp && $prod_start_stamp <= $end_stamp ||
                        $prod_end_stamp >= $start_stamp && $prod_end_stamp <= $end_stamp
                    ) {
                        $total_places += $match_product->BookedQTY;
                    }
                }
            }
        }

        return $total_places;
    }
}