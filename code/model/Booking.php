<?php

use ilateral\SimpleBookings\Helpers\Syncroniser;

/**
 * A single booking that can contain multiple "resources". Each resource is linked to
 * a "BookableProduct" and has a start and end date.
 * 
 * @category SilverstripeModule
 * @package  SimpleBookings
 * @author   ilateral <info@ilateral.co.uk>
 * @license  https://spdx.org/licenses/BSD-3-Clause.html BSD-3-Clause
 * @link     https://github.com/i-lateral/silverstripe-commerce-simplebookings
 */
class Booking extends DataObject implements PermissionProvider
{

    /**
     * DB/Casted fields that will be synced to/from an order on write.
     * This is an array, where keys are the booking fields and values
     * are the order fields 
     * 
     * @var array
     */
    private static $fields_to_sync = array(
        "FirstName" => "FirstName",
        "Surname" => "Surname",
        "Email" => "Email",
        "PhoneNumber" => "PhoneNumber"
    );

    private static $db = array(
        "Start"         => "SS_Datetime",
        "End"           => "SS_Datetime",
        "FirstName"     => "Varchar(255)",
        "Surname"       => "Varchar(255)",
        "Email"         => "Varchar(255)",
        "PhoneNumber"   => "Varchar(255)",
        "PartySize"     => "Int",
        "DisableSync"   => "Boolean"
    );

    private static $has_one = array(
        "Order"     => "Order",
        "Customer"  => "Member"
    );

    private static $has_many = array(
        "Resources" => "BookingResource"
    );

    private static $casting = array(
        "Overbooked"    => "Boolean",
        "ProductsHTML"  => "HTMLText",
        "TotalCost"     => "Currency"
    );

    private static $field_labels = array(
        "Start"         => "Start Date/Time",
        "DisableSync"   => "Disable automatic order sync"
    );

    private static $summary_fields = array(
        "ID"                => "ID",
        "Start"             => "Start",
        "End"               => "End",
        "ProductsHTML"      => "Products",
        "FirstName"         => "First Name",
        "Surname"           => "Surname",
        "Email"             => "Email",
        "Order.OrderNumber" => "Order"
    );

    /**
     * Default sord order of records from the DB
     *
     * @var    array
     * @config
     */
    private static $default_sort = array(
        "Start" => "DESC",
        "End"   => "DESC"
    );

    /**
     * Link to view this item in the CMS
     *
     * @return string
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
     * @return string
     */
    public function CMSOrderLink()
    {
        if ($this->Order()->exists()) {
            $order = $this->Order();
            return Controller::join_links(
                "admin",
                OrderAdmin::config()->url_segment,
                $order->ClassName,
                "EditForm",
                "field",
                $order->ClassName,
                "item",
                $order->ID,
                "view"
            );
        }

        return "";
    }

    /**
     * Get a list fo products for this booking
     * 
     * @return ArrayList
     */
    public function getProducts()
    {
        $products = ArrayList::create();

        foreach ($this->Resources() as $resource) {
            $product = BookableProduct::get()->byID($resource->ProductID);

            if (isset($product)) {
                $products->add($product);
            }
        }

        return $products;
    }

    /**
     * Get a list of products associated with this booking as a HTML UL>LI
     * 
     * @return HTMLText
     */
    public function getProductsHTML()
    {
        $html = "<ul>";

        foreach ($this->Resources() as $resource) {
            $product = $resource->Product();

            if ($product->exists()) {
                $html .= "<li>{$product->Title}: {$resource->BookedQTY}</li>";
            }
        }

        $html .= "</ul>";

        $obj = HTMLText::create("ProductsHTML");
        $obj->setValue($html);

        return $obj;
    }

    /**
     * Determine if any of these products are overbooked
     *
     * @return boolean
     */
    public function getOverBooked()
    {
        $overbooked = false;

        foreach ($this->Resources() as $product) {
            if ($product->getPlacesRemaining($product->Start, $product->End) < 0) {
                $overbooked = true;
            }
        }

        return $overbooked;
    }

    /**
     * Get the total cost of this booking, based on all products added
     * and the total number of days
     *
     * @return float
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
     * @return string
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

    /**
     * Update the end date to be based on the latest end date
     * 
     * @return void
     */
    public function updateEndDate()
    {
        $end = new DateTime($this->Start);
        $flag = false;

        foreach ($this->Resources() as $resource) {
            $new_end = new DateTime($resource->End);

            if ($new_end > $end) {
                $end = $new_end;
                $flag = true;
            }
        }

        $this->End = $end->format("Y-m-d H:i:s");
        return $flag;
    }

    /**
     * {@inheritdoc}
     * 
     * @return FieldList
     */
    public function getCMSFields()
    {
        $self = $this;
        $this->beforeUpdateCMSFields(
            function ($fields) use ($self) {
                $fields->removeByName("End");
                $fields->removeByName("CustomerID");

                // Hide Order Field
                $fields->replaceField(
                    "OrderID",
                    HiddenField::create("OrderID")
                );

                // Setup calendars on date fields
                $start_field = $fields->dataFieldByName("Start");

                if ($start_field) {
                    $start_field
                        ->getDateField()
                        ->setConfig("showcalendar", true);
                }

                // Add editable fields to manage quantity
                $resources_field = $fields->dataFieldByName("Resources");

                if ($resources_field) {
                    $config = $resources_field->getConfig();
                    
                    $alerts = array(
                        'PlacesRemaining' => array(
                            'comparator' => 'less',
                            'patterns' => array(
                                '0' => array(
                                    'status' => 'alert',
                                    'message' => _t(
                                        "SimpleBookings.OverBooked",
                                        'This resource is Over Booked'
                                    ),
                                ),
                            )
                        )
                    );

                    $config
                        ->removeComponentsByType("GridFieldDeleteAction")
                        ->removeComponentsByType("GridFieldRelationSearch")
                        ->removeComponentsByType("GridFieldAddExistingAutocompleter")
                        ->addComponent(new GridFieldDeleteAction())
                        ->addComponent(new GridFieldRecordHighlighter($alerts));
                    
                    $edit_form = $config->getComponentByType("GridFieldDetailForm");

                    if (isset($edit_form)) {
                        $edit_form->setItemRequestClass(
                            BookingResourceDetailForm_ItemRequest::class
                        );
                    }
                    
                    $fields->removeByName("Resources");
                    
                    $fields->addFieldToTab(
                        "Root.Main",
                        $resources_field
                    );
                }

                // Add has one picker field.
                $fields->addFieldsToTab(
                    'Root.Contact',
                    array(
                        TextField::create(
                            "FirstName",
                            $this->fieldLabel("FirstName")
                        ),
                        TextField::create(
                            "Surname",
                            $this->fieldLabel("Surname")
                        ),
                        TextField::create(
                            "Email",
                            $this->fieldLabel("Email")
                        ),
                        TextField::create(
                            "PhoneNumber",
                            $this->fieldLabel("PhoneNumber")
                        ),
                        HeaderField::create(
                            "CustomerHeader",
                            _t(
                                "SimpleBookings.LinkToUser",
                                "Link to an existing user account?"
                            )
                        ),
                        HasOnePickerField::create(
                            $self,
                            'CustomerID',
                            _t("SimpleBookings.CustomerInfo", 'Customer Info'),
                            $self->Customer(),
                            _t(
                                "SimpleBookings.SelectExistingCustomer",
                                'Select Existing Customer'
                            )
                        )->enableCreate(_t("SimpleBookings.AddNewCustomer", 'Add New Customer'))
                        ->enableEdit()
                    )
                );

                // Setup a field to handle order association
                $fields->addFieldToTab(
                    "Root.Order",
                    $order_field = HasOnePickerField::create(
                        $self,
                        'OrderID',
                        _t("SimpleBookings.LinkToOrder", 'Link to an Order'),
                        $self->Order(),
                        _t(
                            "SimpleBookings.SelectExistingOrder",
                            'Select Existing Order'
                        )
                    )->enableEdit()
                    ->enableCreate(_t("SimpleBookings.AddNewOrder", 'Add New Order'))
                );

                $order_field
                    ->getConfig()
                    ->removeComponentsByType(GridFieldDetailForm::class)
                    ->addComponent(new OrdersGridFieldDetailForm());
            }
        );
        
        return parent::getCMSFields();
    }

    /**
     * {@inheritdoc}
     * 
     * @return array
     */
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
     * @param Member $member Either a Member object or an Int
     *
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
        $order = $this->Order();
        
        if (!$order->exists()) {
            $order = Estimate::create();
            $order->write();
        }

        $sync = Syncroniser::create($this, $order)
            ->setSyncProducts(true)
            ->bookingToOrder();

        $this->extend("afterSync", $order, $sync);
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
        foreach ($this->Resources() as $product) {
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
            $diff = ($quantity + $spaces) - $product->getAvailablePlaces();

            if ($diff < 0) {
                $diff = 0;
            }

            $this->Resources()->add($product);
        }

        $this->updateEndDate();
    }

    /**
     * Perform post write database functions
     * 
     * @return void
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (!$this->DisableSync) {
            $this->sync();
        }
    }

    /**
     * Perform post delete functions
     *
     * @return void
     */
    public function onAfterDelete()
    {
        parent::onAfterDelete();

        // Clean up bookings on delete
        foreach ($this->Resources() as $resource) {
            $resource->delete();
        }

        // Ensure that the booking clears up after itself
        if ($this->OrderID) {
            $order = $this->Order();
            $order->BookingID = 0;
            $order->write();
        }
    }
}