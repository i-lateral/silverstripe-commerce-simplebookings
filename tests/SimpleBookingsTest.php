<?php

/**
 * Unit tests for the simple bookings functionality
 * 
 * @author Mo <morven@ilateral.co.uk>
 * @package simplebookings
 * @subpackage tests
 */
class SimpleBookingsTest extends SapphireTest
{

    /** 
     * Defines the fixture file to use for this test class
     *
     */
    protected static $fixture_file = 'BookingsTest.yml';

    /**
     * Test if the booked spaces algorythm returns the
     * correct results when we have a booking that starts
     * before but ends inside an existing booking
     * 
     * @return void
     */
    public function testBookedSpacesStart()
    {
        $room = $this->objFromFixture(
            "BookableProduct",
            "fancyroom"
        );

        $start = "2017-06-14 15:00:00";
        $end = "2017-06-17 11:00:00";

        // Check that we find the correct number of 
        // already booked spaces in thei time period
        $total_places = SimpleBookings::getTotalBookedSpaces(
            $start,
            $end,
            $room->ID
        );

        $this->assertEquals(2, $total_places);
    }

    /**
     * Test if the booked spaces algorythm returns the
     * correct results when we have a booking that ends
     * after but starts inside an existing booking
     * 
     * @return void
     */
    public function testBookedSpacesEnd()
    {
        $room = $this->objFromFixture(
            "BookableProduct",
            "fancyroom"
        );

        $start = "2017-06-17 15:00:00";
        $end = "2017-06-20 11:00:00";

        // Check that we find the correct number of 
        // already booked spaces in thei time period
        $total_places = SimpleBookings::getTotalBookedSpaces(
            $start,
            $end,
            $room->ID
        );

        $this->assertEquals(2, $total_places);
    }

    /**
     * Test if the booked spaces algorythm returns the
     * correct results when we have a booking that does
     * not overlap (is before) another booking.
     * 
     * @return void
     */
    public function testBookedSpacesBefore()
    {
        $room = $this->objFromFixture(
            "BookableProduct",
            "fancyroom"
        );

        $start = "2017-06-12 15:00:00";
        $end = "2017-06-16 11:00:00";

        // Check that we find the correct number of 
        // already booked spaces in thei time period
        $total_places = SimpleBookings::getTotalBookedSpaces(
            $start,
            $end,
            $room->ID
        );

        $this->assertEquals(0, $total_places);
    }

    /**
     * Test if the booked spaces algorythm returns the
     * correct results when we have a booking that does
     * not overlap (is after) another booking.
     * 
     * @return void
     */
    public function testBookedSpacesAfter()
    {
        $room = $this->objFromFixture(
            "BookableProduct",
            "fancyroom"
        );

        $start = "2017-06-18 15:00:00";
        $end = "2017-06-25 11:00:00";

        // Check that we find the correct number of 
        // already booked spaces in thei time period
        $total_places = SimpleBookings::getTotalBookedSpaces(
            $start,
            $end,
            $room->ID
        );

        $this->assertEquals(0, $total_places);
    }
}