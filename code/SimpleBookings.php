<?php


/**
 * Config class for this module to hold global settings.
 *
 * @package SimpleBookings
 * @author  ilateral (http://ilateralweb.co.uk)
 */
class SimpleBookings extends ViewableData
{

    /**
     * Do BookableProducts lock the shopping cart (so
     * they cannot be edited)?
     *
     * @var    boolean
     * @config 
     */
    private static $lock_cart = true;

    /**
     * Do BookableProducts contain a deliverable component
     * (for example tickets to be posted). By default this
     * module assumes no.
     *
     * @var    boolean
     * @config 
     */
    private static $allow_delivery = false;

    /**
     * Some of the codebase results in querying getTotalBookedSpaces
     * repeatedly. Try to overcome performance bottlenecks
     * by caching product ID's VS a number of spaces
     *
     * @var array
     */
    private static $cached_spaces = [];

    /**
     * Takes two dates formatted as YYYY-MM-DD and creates an inclusive
     * array of the timecodes between the from and to dates.
     * 
     * Thanks to this stack overflow post:
     * http://stackoverflow.com/questions/4312439/php-return-all-dates-between-two-dates-in-an-array
     *
     * @param string $date_from The starting date
     * @param string $date_to   The end date
     * @param int    $interval  Time period (seconds) to use to make the array
     *
     * @return array
     */
    public static function createDateRangeArray($date_from, $date_to, $interval)
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
     * @param string $date_from The starting date
     * @param string $date_to   The end date
     * @param int    $ID        The ID of the product we are trying to book
     * 
     * @return int
     */
    public static function getTotalBookedSpaces($date_from, $date_to, $ID)
    {
        // Attempt to return spaces from local cache if
        // possible
        $cache = self::$cached_spaces;

        if (in_array($ID, array_keys($cache))) {
            return $cache[$ID];
        }

        $db = DB::get_conn();

        // First get a list of days between the start and end date
        $total_places = 0;        
        $format = "%Y-%m-%d";

        $start_field = $db->formattedDatetimeClause(
            'Start',
            $format
        );
        $end_field = $db->formattedDatetimeClause(
            'End',
            $format
        );

        $date_filter = [
            $start_field . ' <= ?' =>  $date_to,
            $start_field . ' >= ?' =>  $date_from,
            $end_field . ' >= ?' =>  $date_from,
            $end_field . ' <= ?' =>  $date_to,
            "ProductID" => $ID
        ];

        // Get all bookings with a start date within
        // the date range
        $bookings = BookingResource::get()->where(
            $date_filter
        );
        
        // Now get all products inside these bookings that
        // match our date range and tally the results
        foreach ($bookings as $match_product) {
            $start_stamp = strtotime($date_from);
            $end_stamp = strtotime($date_to);
            $prod_start_stamp = strtotime($match_product->Start);
            $prod_end_stamp = strtotime($match_product->End);

            if ($prod_start_stamp >= $start_stamp && $prod_start_stamp <= $end_stamp 
                || $prod_start_stamp <= $start_stamp && $prod_end_stamp >= $end_stamp 
                || $prod_end_stamp >= $start_stamp && $prod_end_stamp <= $end_stamp
            ) {
                $total_places += $match_product->BookedQTY;
            }
        }

        $date_filter = [
            $start_field . ' <= ?' =>  $date_to,
            $start_field . ' >= ?' =>  $date_from,
            $end_field . ' >= ?' =>  $date_from,
            $end_field . ' <= ?' =>  $date_to
        ];

        // Get all bookings with a start date within
        // the date range
        $allocations = ResourceAllocation::get()->where($date_filter);

        $all_allocated = false;

        foreach ($allocations as $allocation) {
            if ($allocation->AllocateAll) {
                $all_allocated = true;
            }

            $resources = $allocation->Resources()->Filter('ID', $ID);
            
            foreach ($resources as $product) {
                if ($all_allocated || $product->AllocateAll) {
                    $total_places += $product->AvailablePlaces;
                } else {
                    $start_stamp = strtotime($date_from);
                    $end_stamp = strtotime($date_to);
                    $alloc_start_stamp = strtotime($allocation->Start);
                    $alloc_end_stamp = strtotime($allocation->End);

                    if ($alloc_start_stamp >= $start_stamp && $alloc_start_stamp <= $end_stamp 
                        || $alloc_start_stamp <= $start_stamp && $alloc_end_stamp >= $end_stamp 
                        || $alloc_end_stamp >= $start_stamp && $alloc_end_stamp <= $end_stamp
                    ) {
                        if ($product->Increase) {
                            $total_places -= $product->Quantity;
                        } else {
                            $total_places += $product->Quantity;
                        }
                    }
                }
            }
        }

        // Add this result to the local cache
        self::$cached_spaces[$ID] = $total_places;

        return $total_places;
    }
}