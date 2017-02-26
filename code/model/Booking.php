<?php

class Booking extends DataObject implements PermissionProvider
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

    private static $casting = array(
        "ProductsHTML"  => "HTMLText"
    );

    private static $summary_fields = array(
        "Title"         => "Title",
        "Start"         => "Start",
        "End"           => "End",
        "ProductsHTML"  => "Products"
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

    public function getProductsHTML()
    {
        $html = "<ul>";

        foreach ($this->Products() as $product) {
            $html .= "<li>{$product->Title}: {$product->BookedQTY}</li>";
        }

        $html .= "</ul>";

        $obj = HTMLText::create("ProductsHTML");
        $obj->setValue($html);

        return $obj;
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

    public function providePermissions()
    {
        return array(
            "BOOKING_VIEW_BOOKINGS" => array(
                'name' => 'View any booking',
                'help' => 'Allow user to view any booking',
                'category' => 'Bookings',
                'sort' => 99
            ),
            "BOOKING_CREATE_BOOKINGS" => array(
                'name' => 'Create a booking',
                'help' => 'Allow user to create a booking',
                'category' => 'Bookings',
                'sort' => 98
            ),
            "BOOKING_EDIT_BOOKINGS" => array(
                'name' => 'Edit any booking',
                'help' => 'Allow user to edit any booking',
                'category' => 'Bookings',
                'sort' => 97
            ),
            "BOOKING_DELETE_BOOKINGS" => array(
                'name' => 'Delete any booking',
                'help' => 'Allow user to delete any booking',
                'category' => 'Bookings',
                'sort' => 96
            )
        );
    }

    /**
     * Return a member object, based on eith the passed param or
     * getting the currently logged in Member.
     * 
     * @param $member Either a Member object or an Int
     * @return Member | Null
     */
    protected function getMember($member = null)
    {
        if ($member && $member instanceof Member) {
            return $member;
        } elseif (is_numeric($member)) {
            return Member::get()->byID($member);
        } else {
            return Member::currentUser();
        }
    }

    /**
     * Only users with VIEW admin rights can view
     *
     * @return Boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extend('canView', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, array("ADMIN", "BOOKING_VIEW_BOOKINGS"))) {
            return true;
        }

        return false;
    }

    /**
     * Only users with create admin rights can create
     *
     * @return Boolean
     */
    public function canCreate($member = null)
    {
        $extended = $this->extend('canCreate', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, array("ADMIN", "BOOKING_CREATE_BOOKINGS"))) {
            return true;
        }

        return false;
    }

    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, array("ADMIN", "BOOKING_EDIT_BOOKINGS"))) {
            return true;
        }

        return false;
    }

    /**
     * Only users with Delete Permissions can delete Bookings
     *
     * @return Boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, array("ADMIN", "BOOKING_DELETE_BOOKINGS"))) {
            return true;
        }

        return false;
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