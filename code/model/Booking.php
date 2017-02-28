<?php

class Booking extends DataObject implements PermissionProvider
{
    private static $db = array(
        "Start" => "Date",
        "End"   => "Date"
    );

    private static $has_one = array(
        "Order" => "Order",
        "Customer" => "Member"
    );

    private static $many_many = array(
        "Products" => "BookableProduct" 
    );

    private static $many_many_extraFields = array(
        "Products" => array(
            "BookedQTY" => "Int",
            "OverBooked"=> "Int"
        )
    );

    private static $casting = array(
        "OverBooked"    => "Boolean",
        "ProductsHTML"  => "HTMLText",
        "TotalCost"     => "Currency"
    );

    private static $summary_fields = array(
        "Start"         => "Start",
        "End"           => "End",
        "ProductsHTML"  => "Products",
        "Customer.FirstName" => "First Name",
        "Customer.Surname" => "Surname",
        "Customer.Email" => "Email"
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
     * Link to view this item's order in the CMS
     *
     * @return String
     */
    public function CMSOrderLink()
    {
        return Controller::join_links(
            "admin",
            OrderAdmin::config()->url_segment,
            "Order",
            "EditForm",
            "field",
            "Order",
            "item",
            $this->OrderID,
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
     * Determine if any of these products are overbooked
     *
     * @return Boolean
     */
    public function getOverBooked()
    {
        $overbooked = 0;

        foreach ($this->Products() as $product) {
            if ($product->OverBooked) {
                $overbooked += $product->OverBooked;
            }
        }

        return ($overbooked > 0) ? true : false;
    }

    /**
     * Get the total cost of this booking, based on all products added
     * and the total number of days
     *
     * @return Float
     */
    public function getTotalCost()
    {
        $total_days = count(SimpleBookings::create_date_range_array(
            $this->Start,
            $this->End
        ));

        $price = 0;

        foreach ($this->Products() as $product) {
            $single_price = $product->PriceAndTax;
            $price = ($single_price * $product->BookedQTY) * $total_days;
        }

        return $price;
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

        // Hide Order Field
        $fields->replaceField(
            "OrderID",
            HiddenField::create("OrderID")
        );

        // Setup calendars on date fields
        $start_field = $fields->dataFieldByName("Start");
        $end_field = $fields->dataFieldByName("End");

        if ($start_field && $end_field) {
            $fields->addFieldToTab(
                'Root.Main',
                HeaderField::create(
                    "DatesHeader",
                    _t("SimpleBookings.SelectStartEndDate", "Select a Start and End Date")
                ),
                'Start'
            );
            

            $fields->removeByName("Start");
            $fields->removeByName("End");

            $start_field
                ->setConfig("showcalendar", true);

            $end_field
                ->setConfig("showcalendar", true);

            $dates_field = CompositeField::create(
                $start_field,
                $end_field
            )->setColumnCount(2)
            ->setTitle("DatesField")
            ->addExtraClass("booking-dates-field");

            $fields->addFieldToTab(
                "Root.Main",
                $dates_field
            );
        }

        // Add editable fields to manage quantity
        $products_field = $fields->dataFieldByName("Products");

        if ($products_field) {
            $config = $products_field->getConfig();

            $editable_cols = new GridFieldEditableColumns();
            $editable_cols
                ->setDisplayFields(array(
                    'CMSThumbnail' => array(
                        'field' => 'LiteralField',
                        'title' => _t("SimpleBookings.Thumbnail", "Thumbnail")
                    ),
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
                    ),
                    'OverBooked' => array(
                        'field' => 'ReadonlyField',
                        'title' => _t("SimpleBookings.OverBooked", "Overbooked")
                    )
                ));
            
            $alerts = array(
				'OverBooked' => array(
					'comparator' => 'greater',
					'patterns' => array(
						'0' => array(
							'status' => 'alert',
							'message' => _t("SimpleBookings.OverBooked", 'This product is Over Booked'),
						),
					)
				)
			);


        	$config
                ->removeComponentsByType("GridFieldAddNewButton")
                ->removeComponentsByType("GridFieldDeleteAction")
                ->removeComponentsByType("GridFieldEditButton")
                ->removeComponentsByType("GridFieldDetailForm")
                ->removeComponentsByType("GridFieldDataColumns")
                ->addComponent($editable_cols)
                ->addComponent(new GridFieldDeleteAction(true))
                ->addComponent(new GridFieldRecordHighlighter($alerts));
            
            $fields->removeByName("Products");
            
            $fields->addFieldToTab(
                "Root.Main",
                $products_field
            );
        }

        // Add has one picker field.
        $fields->addFieldsToTab(
            'Root.Customer',
            array(
                HeaderField::create(
                    "CustomerHeader",
                    _t("SimpleBookings.CustomerDetails", "Customer Details")
                ),
                HasOnePickerField::create(
                    $this,
                    'CustomerID',
                    _t("SimpleBookings.CustomerInfo",'Customer Info'),
                    $this->Customer(),
                    _t("SimpleBookings.SelectExistingCustomer", 'Select Existing Customer')
                )->enableCreate(_t("SimpleBookings.AddNewCustomer", 'Add New Customer'))
                ->enableEdit()
            )
        );

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
     * Check all products and set any flags needed
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        foreach ($this->Products() as $product) {
            $quantity = $product->BookedQty;
            $spaces = SimpleBookings::get_total_booked_spaces($this->Start, $this->End, $product->ID);
            $diff = $spaces - $product->AvailablePlaces;

            if ($diff > 0) {
                $this->Products()->add(
                    $product,
                    array(
                        "BookedQty" => $quantity,
                        "OverBooked"=> $diff
                    )
                );
            }
        }
    }

    /**
     * Create a Booking when the order is marked as paid
     *
     */
    public function onAfterDelete()
    {
        parent::onAfterDelete();

        // Ensure that the booking clears up after itself
        if ($this->OrderID) {
            $order = $this->Order();
            $order->BookingID = 0;
            $order->write();
        }
    }
}