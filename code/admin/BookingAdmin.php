<?php

class BookingAdmin extends ModelAdmin
{
    private static $url_segment = 'bookings';

    private static $menu_title = 'Bookings';

    private static $menu_priority = 4;

    private static $managed_models = array(
        'Booking' => array('title' => 'Bookings')
    );

    private static $model_importers = array(
        'Booking' => 'CSVBulkLoader',
    );

    public $showImportForm = array('Booking');
}