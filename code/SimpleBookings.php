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
     * array of the timecodes between the from and to dates.
     * 
     * Thanks to this stack overflow post:
     * http://stackoverflow.com/questions/4312439/php-return-all-dates-between-two-dates-in-an-array
     *
     * @param date_from The starting date
     * @param date_to The end date
     * @param interval a time period in seconds that will be used to make the array 
     * @return array
     */
    public static function create_date_range_array($date_from, $date_to, $interval)
    {
        $range = array();
        $time_from = strtotime($date_from);
        $time_to = strtotime($date_to);

        if ($time_to >= $time_from) {
            while ($time_from < $time_to) {
                $range[] = $time_from;
                $time_from += $interval;
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
        $total_places = 0;
        $product = BookableProduct::get()->byID($ID);

        if ($product) {
            // Get all bookings with a start date within
            // the date range
            $bookings_start = Booking::get()->filter(array(
                "Start:LessThanOrEqual" => $date_to,
                "Start:GreaterThanOrEqual" => $date_from,
                "Resources.ProductID" => $ID
            ));

            // Get all bookings with a start and end date within
            // the date range
            $bookings_within = Booking::get()->filter(array(
                "Start:LessThanOrEqual" => $date_from,
                "End:GreaterThanOrEqual" => $date_to,
                "Resources.ProductID" => $ID
            ));

            // Get all bookings with an end date within
            // the date range
            $bookings_end = Booking::get()->filter(array(
                "End:LessThanOrEqual" => $date_to,
                "End:GreaterThanOrEqual" => $date_from,
                "Resources.ProductID" => $ID
            ));

            // Create a new list of all bookings and clean it
            // of duplicates
            $all_bookings = ArrayList::create();
            $all_bookings->merge($bookings_start);
            $all_bookings->merge($bookings_end);
            $all_bookings->merge($bookings_within);
            $all_bookings->removeDuplicates();
            
            // Now get all products inside these bookings that
            // match our date range and tally the results
            foreach ($all_bookings as $booking) {
                $resources = $booking->Resources()->Filter('ProductID',$ID);
                foreach ($resources as $match_product) {
                    $start_stamp = strtotime($date_from);
                    $end_stamp = strtotime($date_to);
                    $prod_start_stamp = strtotime($match_product->Start);
                    $prod_end_stamp = strtotime($match_product->End);

                    if (
                        $prod_start_stamp >= $start_stamp && $prod_start_stamp <= $end_stamp ||
                        $prod_start_stamp <= $start_stamp && $prod_end_stamp >= $end_stamp ||
                        $prod_end_stamp >= $start_stamp && $prod_end_stamp <= $end_stamp
                    ) {
                        $total_places += $match_product->BookedQTY;
                    }
                }
            }

            // Get all bookings with a start date within
            // the date range
            $allocations_start = ResourceAllocation::get()->filter(array(
                "Start:LessThanOrEqual" => $date_to,
                "Start:GreaterThanOrEqual" => $date_from
            ));

            // Get all bookings with a start and end date within
            // the date range
            $allocations_within = ResourceAllocation::get()->filter(array(
                "Start:LessThanOrEqual" => $date_from,
                "End:GreaterThanOrEqual" => $date_to
            ));

            // Get all bookings with an end date within
            // the date range
            $allocations_end = ResourceAllocation::get()->filter(array(
                "End:LessThanOrEqual" => $date_to,
                "End:GreaterThanOrEqual" => $date_from
            ));

            // Create a new list of all bookings and clean it
            // of duplicates
            $all_allocations = ArrayList::create();
            $all_allocations->merge($allocations_start);
            $all_allocations->merge($allocations_end);
            $all_allocations->merge($allocations_within);
            $all_allocations->removeDuplicates();

            $all_allocated = false;

            foreach ($all_allocations as $allocation) {
                if ($allocation->AllocateAll) {
                    $all_allocated = true;
                    break;
                }
                $resources = $allocation->Resources()->Filter('ID',$ID);
                foreach ($resources as $product) {
                    if ($product->AllocateAll) {
                        $all_allocated = true;
                        break;
                    }
                    $start_stamp = strtotime($date_from);
                    $end_stamp = strtotime($date_to);
                    $alloc_start_stamp = strtotime($allocation->Start);
                    $alloc_end_stamp = strtotime($allocation->End);
                    if (
                        $alloc_start_stamp >= $start_stamp && $alloc_start_stamp <= $end_stamp ||
                        $alloc_start_stamp <= $start_stamp && $alloc_end_stamp >= $end_stamp ||
                        $alloc_end_stamp >= $start_stamp && $alloc_end_stamp <= $end_stamp
                    ) {
                        if ($product->Increase) {
                            $total_places -= $product->Quantity;
                        } else {   
                            $total_places += $product->Quantity;
                        }
                    }
                }
            }

            if ($all_allocated) {
                $total_places = $product->AvailablePlaces;
            }
        }

        return $total_places;
    }
}