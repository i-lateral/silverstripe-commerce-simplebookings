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
     * Takes two dates formatted as YYYY-MM-DD and creates an inclusive
     * array of the dates between the from and to dates.
     * 
     * Thanks to this stack overflow post: 
     * http://stackoverflow.com/questions/4312439/php-return-all-dates-between-two-dates-in-an-array
     *
     * @param date_from The starting date
     * @param date_to The end date
     * @return array
     */
    public static function create_date_range_array($date_from, $date_to)
    {
        $range = array();
        $time_from = strtotime($date_from);
        $time_to = strtotime($date_to);

        if ($time_to >= $time_from) {
            array_push($range, date('Y-m-d', $time_from));
            while ($time_from < $time_to) {
                $time_from += 86400;
                array_push($range, date('Y-m-d', $time_from));
            }
        }

        return $range;
    }

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
        $final_bookings = ArrayList::create();
        $total_places = 0;

        // Loop days and check we have spaces available by looping through
        // all bookings with this product and checking now many have
        // already been reserved
        foreach ($days as $day) {
            $bookings = Booking::get()->filter(array(
                "Start:LessThanOrEqual" => $day,
                "End:GreaterThanOrEqual" => $day,
                "Products.ID" => $ID
            ));

            foreach ($bookings as $booking) {
                $final_bookings->add($booking);
            }
        }

        $final_bookings->removeDuplicates();

        // Now loop through the bookings and check the tally the total
        // quantity of bookings
        foreach ($final_bookings as $booking) {
            $matched_product = $booking
                ->Products()
                ->filter("ID", $ID)
                ->first();

            $total_places = $total_places + $matched_product->BookedQTY;
        }

        return $total_places;
    }
}