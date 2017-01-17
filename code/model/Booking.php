<?php

class Booking extends DataObject
{
    private static $db = array(
        "Title" => "Varchar",
        "Start" => "Date",
        "End"   => "Date"
    );

    private static $has_one = array(
        "Order" => "Order"
    );

    private static $many_many = array(
        "Products" => "BookableProduct" 
    );

    private static $many_many_extraFields = array(
        "Products" => array(
            "BookedQTY" => "Int"
        )
    );

    private static $summary_fields = array(
        "Title",
        "Start",
        "End"
    );

    /**
     * Link to view this item in the CMS
     *
     * @return String
     */
    public function CMSViewLink()
    {
        return Controller::join_links(
            "admin",
            BookingAdmin::config()->url_segment,
            "Booking",
            "EditForm",
            "field",
            "Booking",
            "item",
            $this->ID,
            "view"
        );
    }

    /**
     * Link to edit this item in the CMS
     *
     * @return String
     */
    public function CMSEditLink()
    {
        return Controller::join_links(
            "admin",
            BookingAdmin::config()->url_segment,
            "Booking",
            "EditForm",
            "field",
            "Booking",
            "item",
            $this->ID,
            "edit"
        );
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Setup calendars on date fields
        $start_field = $fields->dataFieldByName("Start");
        $end_field = $fields->dataFieldByName("End");

        if ($start_field && $end_field) {
            $start_field
                ->setConfig("showcalendar", true);

            $end_field
                ->setConfig("showcalendar", true);
        }

        // Add editable fields to manage quantity
        $products_field = $fields->dataFieldByName("Products");

        if ($products_field) {
            $config = $products_field->getConfig();

            $editable_cols = new GridFieldEditableColumns();
            $editable_cols
                ->setDisplayFields(array(
                    'Title' => array(
                        'field' => 'ReadonlyField',
                        'title' => _t("SimpleBookings.Title", "Title")
                    ),
                    'Price' => array(
                        'field' => 'ReadonlyField',
                        'title' => _t("SimpleBookings.Price", "Price")
                    ),
                    'StockID' => array(
                        'field' => 'ReadonlyField',
                        'title' => _t("SimpleBookings.StockID", "Stock ID")
                    ),
                    'BookedQTY' => array(
                        'field' => 'TextField',
                        'title' => _t("SimpleBookings.NumbertoBook", "Number to Book")
                    )
                ));


        	$config
                ->removeComponentsByType("GridFieldAddNewButton")
                ->removeComponentsByType("GridFieldEditButton")
                ->removeComponentsByType("GridFieldDetailForm")
                ->removeComponentsByType("GridFieldDataColumns")
                ->addComponent($editable_cols);
        }

        return $fields;
    }

    /**
     * Create a Booking when the order is marked as paid
     *
     */
    public function onAfterDelete()
    {
        // Ensure that the booking clears up after itself
        if ($this->OrderID) {
            $order = $this->Order();
            $order->BookingID = 0;
            $order->write();
        }
    }
}