<?php

namespace ilateral\SimpleBookings\Helpers;

use Order;
use Config;
use Object;
use Booking;
use ArrayData;
use ArrayList;
use OrderItem;
use SimpleBookings;
use BookableProduct;
use BookingResource;
use OrderItemCustomisation;

/**
 * Basic class that assists in syncronising bookings and orders.
 * 
 * @category SilverstripeModule
 * @package  SimpleBookings
 * @author   ilateral <info@ilateral.co.uk>
 * @license  https://spdx.org/licenses/BSD-3-Clause.html BSD-3-Clause
 * @link     https://github.com/i-lateral/silverstripe-commerce-simplebookings
 */
class Syncroniser extends Object
{
    /**
     * Sync related products/resources? If this is set to false
     * then only contact details will be synced.
     * 
     * Otherwise all products will be also be synced
     * 
     * @var boolean
     */
    protected $sync_products = false;

    /**
     * Get the value of sync_products
     * 
     * @return boolean
     */ 
    public function getSyncProducts()
    {
        return $this->sync_products;
    }

    /**
     * Set the value of sync_products
     *
     * @return self
     */ 
    public function setSyncProducts($sync_products)
    {
        $this->sync_products = $sync_products;

        return $this;
    }

    /**
     * The order for this syncronisation
     * 
     * @var Order
     */
    protected $order;

    /**
     * Get the value of order
     * 
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the value of order
     *
     * @return self
     */ 
    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Booking to use for this syncronisation
     * 
     * @var Booking
     */
    protected $booking;

    /**
     * Get the value of booking
     * 
     * @return Booking
     */ 
    public function getBooking()
    {
        return $this->booking;
    }

    /**
     * Set the value of booking
     *
     * @return self
     */ 
    public function setBooking(Booking $booking)
    {
        $this->booking = $booking;

        return $this;
    }

    /**
     * Initialise this object
     * 
     * @param Booking $booking the booking to sync
     * @param Order   $order   the order to sync
     * 
     * @return void 
     */
    public function __construct(Booking $booking, Order $order)
    {
        $this->booking = $booking;
        $this->order = $order;
    }

    /**
     * Syncronise booking info to an order
     * 
     * @return self
     */
    public function bookingToOrder()
    {
        $booking = $this->getBooking();
        $order = $this->getOrder();
        $sync_products = $this->getSyncProducts();
        $write = false;
        $fields_to_sync = Config::inst()->get(
            Booking::class,
            "fields_to_sync"
        );

        // Loop through resources on the booking, find the products and 
        // assign them to an order
        if ($sync_products) {
            foreach ($booking->Resources() as $resource) {
                $product = BookableProduct::get()->byID($resource->ProductID);

                if (isset($product)) {
                    $total_time = count(
                        SimpleBookings::createDateRangeArray(
                            $resource->Start,
                            $resource->End,
                            $product->PricingPeriod
                        )
                    );

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
                        $item = OrderItem::create(
                            array(
                            "Key" => $product->ID,
                            "Title" => $product->Title,
                            "Quantity" => $resource->BookedQTY,
                            "Price" => $product->Price,
                            "TaxRate" => $product->TaxPercent,
                            "StockID" => $product->StockID,
                            "ProductClass" => $product->ClassName,
                            "Stocked" => false,
                            "Deliverable" => false
                            )
                        );
                        $item->ParentID = $order->ID;
                    } else {
                        $item->Quantity = $resource->BookedQTY;
                    }
                    $item->write();

                    // Setup customisation on an order item
                    $customisation = OrderItemCustomisation::create(
                        array(
                        "Title" => _t("SimpleBookings.StartDate", "Start Date"),
                        "Value" => $product->Start,
                        "Price" => 0
                        )
                    );
                    $customisation->write();
                    $item->Customisations()->add($customisation);

                    $customisation = OrderItemCustomisation::create(
                        array(
                        "Title" => _t("SimpleBookings.EndDate", "End Date"),
                        "Value" => $product->End,
                        "Price" => 0
                        )
                    );
                    $customisation->write();
                    $item->Customisations()->add($customisation);

                    $customisation = OrderItemCustomisation::create(
                        array(
                        "Title" => _t("SimpleBookings.LengthOfTime", "Length of Time"),
                        "Value" => $total_time,
                        "Price" => ($product->Price * $total_time) - $product->Price
                        )
                    );
                    $customisation->write();
                    $item->Customisations()->add($customisation);
                }
            }
        }

        // Ensure we sync contact details to the order
        foreach ($fields_to_sync as $b_field => $o_field) {
            $order->{$o_field} = $booking->{$b_field};
        }

        $order->write();

        if ($booking->CustomerID != $order->CustomerID) {
            $booking->CustomerID = $order->CustomerID;
            $write = true;
        }

        if (!$booking->Order()->exists()) {
            $booking->OrderID = $order->ID;
            $write = true;
        }

        if ($write) {
            $booking->write();
        }

        return $this;
    }

    /**
     * Syncronise order info to a booking
     * 
     * @return self
     */
    public function orderToBooking()
    {
        $booking = $this->getBooking();
        $order = $this->getOrder();
        $sync_products = $this->getSyncProducts();
        $fields_to_sync = Config::inst()->get(Booking::class, "fields_to_sync");
        $products = ArrayList::create();
        $start_title = _t("SimpleBookings.StartDate", "Start Date"); 
        $end_title = _t("SimpleBookings.EndDate", "End Date");
        $start_date = null;
        $end_date = null;

        // Does this order contain any bookable products
        foreach ($order->Items() as $item) {
            $product = $item->FindStockItem();

            if ($product && $product instanceof BookableProduct) {
                foreach ($item->Customisations() as $customisation) {
                    if ($customisation->Title == $start_title) {
                        $start_date = $customisation->Value;
                    }

                    if ($customisation->Title == $end_title) {
                        $end_date = $customisation->Value;
                    }
                }

                $products->add(
                    ArrayData::create(
                        array(
                            "Title" => $product->Title,
                            "ID" => $product->ID,
                            "Quantity" => $item->Quantity,
                            "Product" => $product
                        )
                    )
                );
            }
        }

        if ($sync_products) {
            // If we have found bookable products
            if ($products->exists()) {
                $booking->Start = $start_date;
                $booking->End = $end_date;
                $booking->OrderID = $order->ID;
                $booking->write();

                foreach ($products as $product) {
                    $resource = BookingResource::create();
                    $resource->Title = $product->Product->Title;
                    $resource->Start = $start_date;
                    $resource->End = $end_date;
                    $resource->ProductID = $product->Product->ID;
                    $resource->BookedQTY = $product->Quantity;
                    $resource->write();

                    $booking->Resources()->add($resource);
                }

                $order->BookingID = $booking->ID;
            }
        }

        // Sync order fields with a booking (if we need to)
        if ($products->exists()) {
            foreach ($fields_to_sync as $b_field => $o_field) {
                $booking->{$b_field} = $order->{$o_field};
            }
            $booking->write();
        }

        return $this;
    }
}