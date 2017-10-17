<?php

class Booking extends DataObject implements PermissionProvider
{
    private static $db = array(
        "Start" => "SS_Datetime",
        "End"   => "SS_Datetime"
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
            "Start" => "SS_Datetime",
            "End"   => "SS_Datetime"
        )
    );

    private static $casting = array(
        "Overbooked"    => "Boolean",
        "ProductsHTML"  => "HTMLText",
        "TotalCost"     => "Currency"
    );

    private static $summary_fields = array(
        "ID"                => "ID",
        "Start"             => "Start",
        "End"               => "End",
        "ProductsHTML"      => "Products",
        "Customer.FirstName"=> "First Name",
        "Customer.Surname"  => "Surname",
        "Customer.Email"    => "Email",
        "Order.OrderNumber" => "Order"
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
        $overbooked = false;

        foreach ($this->Products() as $product) {
            if ($product->PlacesRemaining($product->Start, $product->End) < 0) {
                $overbooked = true;
            }
        }

        return $overbooked;
    }

    /**
     * Get the total cost of this booking, based on all products added
     * and the total number of days
     *
     * @return Float
     */
    public function getTotalCost()
    {
        $order = $this->Order();

        if ($order->exists()) {
            return $order->obj("Total")->getValue();
        }

        return 0;
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

            $start_field
                ->getDateField()
                ->setConfig("showcalendar", true);

            $end_field
                ->getDateField()
                ->setConfig("showcalendar", true);
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
                    'Start' => array(
                        'field' => 'DateTimeField',
                        'title' => _t("SimpleBookings.StartDateTime", "Start Date/Time")
                    ),
                    'End' => array(
                        'field' => 'DateTimeField',
                        'title' => _t("SimpleBookings.EndDateTime", "End Date/Time")
                    )
                ));
            
            $alerts = array(
				'PlacesRemaining' => array(
					'comparator' => 'less',
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
     * Syncronise this booking with an order
     *
     * @return void
     */
    public function sync()
    {
        // If we have no order assigned, generate an estimate and
        // link to this booking
        if (!$this->Order()->exists()) {
            $order = Estimate::create();
            $order->write();
        } else {
            $order = $this->Order();
        }
        
        foreach ($this->Products() as $product) {
            $total_time = count(SimpleBookings::create_date_range_array(
                $product->Start,
                $product->End,
                $product->PricingPeriod
            ));
            $item = null;

            // Clean and reset any matched products
            foreach ($order->Items() as $order_item) {
                $stock_item = $order_item->FindStockItem();

                if ($stock_item && $stock_item->ID == $product->ID) {
                    $item = $order_item;

                    $items_to_remove = [
                        _t("SimpleBookings.StartDate", "Start Date"),
                        _t("SimpleBookings.EndDate", "End Date"),
                        _t("SimpleBookings.LengthOfTime", "Length of Time")
                    ];

                    foreach ($order_item->Customisations() as $customisation) {
                        if (in_array($customisation->Title, $items_to_remove)) {
                            $customisation->delete();
                        }
                    }
                }
            }

            // If we haven't found an existing order item, create a new one
            if (!$item) {
                $item = OrderItem::create(array(
                    "Key" => $product->ID,
                    "Title" => $product->Title,
                    "Quantity" => $product->BookedQTY,
                    "Price" => $product->Price,
                    "TaxRate" => $product->TaxPercent,
                    "StockID" => $product->StockID,
                    "ProductClass" => $product->ClassName,
                    "Stocked" => false,
                    "Deliverable" => false
                ));
                $item->ParentID = $order->ID;
            } else {
                $item->Quantity = $product->BookedQTY;
            }
            $item->write();

            // Setup customisation on an order item
            $customisation = OrderItemCustomisation::create(array(
                "Title" => _t("SimpleBookings.StartDate", "Start Date"),
                "Value" => $product->Start,
                "Price" => 0
            ));
            $customisation->write();
            $item->Customisations()->add($customisation);

            $customisation = OrderItemCustomisation::create(array(
                "Title" => _t("SimpleBookings.EndDate", "End Date"),
                "Value" => $product->End,
                "Price" => 0
            ));
            $customisation->write();
            $item->Customisations()->add($customisation);

            $customisation = OrderItemCustomisation::create(array(
                "Title" => _t("SimpleBookings.LengthOfTime", "Length of Time"),
                "Value" => $total_time,
                "Price" => ($product->Price * $total_time) - $product->Price
            ));
            $customisation->write();
            $item->Customisations()->add($customisation);
        }

        if (!$this->Order()->exists()) {
            $this->OrderID = $order->ID;
            $this->write();
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
     * Perform pre-write database functions
     *
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Check availability of assigned products and ensure they are
        // booked within the boundries of the booking
        foreach ($this->Products() as $product) {
            $quantity = $product->BookedQTY;
            $start = ($product->Start) ? $product->Start : $this->Start;
            $end = ($product->End) ? $product->End : $this->End;
            $start_stamp = strtotime($start);
            $end_stamp = strtotime($end);

            // Dont allow products to be booked outside of this
            // Bookings time scale
            if ($start_stamp < strtotime($this->Start) || $start_stamp > strtotime($this->End)) {
                $start = $this->Start;
            }

            if ($end_stamp > strtotime($this->End) || $end_stamp < strtotime($this->Start)) {
                $end = $this->End;
            }

            $spaces = $product->getBookedPlaces($start, $end);
            $diff = ($quantity + $spaces) - $product->AvailablePlaces;

            if ($diff < 0) {
                $diff = 0;
            }

            $this->Products()->add(
                $product,
                array(
                    "BookedQTY" => $quantity,
                    "OverBooked"=> $diff,
                    "Start" => $start,
                    "End" => $end
                )
            );
        }
    }

    /**
     * Perform post write database functions
     * 
     * @return void
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $this->sync();
    }

    /**
     * Perform post delete functions
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