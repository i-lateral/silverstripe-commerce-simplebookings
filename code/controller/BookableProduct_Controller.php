<?php

class BookableProduct_Controller extends Product_Controller
{
    public static $allowed_actions = array(
        'Form'
    );

    public function Form()
    {
        $form = parent::Form();
        $object = $this->dataRecord;
        $fields = $form->Fields();

        $fields->removeByName("Quantity");

        $fields->push(
            HeaderField::create(
                "BookHeading",
                _t("SimpleBookings.BookNow","Book Now"),
                2
            )
        );
        
        $fields->push(
            BookingDateField::create("StartDate")
                ->setConfig('dateformat', 'dd-MM-yyyy')
                ->setConfig('showcalendar', true)
        );

        $fields->push(
            BookingDateField::create("EndDate")
                ->setConfig('dateformat', 'dd-MM-yyyy')
                ->setConfig('showcalendar', true)
        );

        $fields->push(
            QuantityField::create(
                'Quantity',
                _t(
                    "SimpleBookings.NumberOfSpaces",
                    "Number of Spaces"
                )
            )->setValue('1')
            ->addExtraClass('checkout-additem-quantity')
        );

        $this->extend("updateBookingForm", $form);

        return $form;
    }

    public function doAddItemToCart($data, $form)
    {
        $time_start = strtotime($data["StartDate"]);
        $time_end = strtotime($data["EndDate"]);
        $time_now = strtotime("today");
        
        // Check the start date is in the future
        if ($time_start <= $time_now) {
            $form->addErrorMessage(
                "StartDate",
                _t("SimpleBookings.StartDateError", "You must choose a date that is in the future"),
                "bad"
            );

            return $this->redirectBack();
        }        

        // Check if we have set an end date that is after the start date
        if ($time_start < $time_now) {
            $form->addErrorMessage(
                "EndDate",
                _t("SimpleBookings.EndDateError", "You must choose an end date that is after the starting date"),
                "bad"
            );

            return $this->redirectBack();
        }

        $classname = $data["ClassName"];
        $id = $data["ID"];
        $customisations = array();
        
        $cart = ShoppingCart::get();
        
        if ($object = $classname::get()->byID($id)) {
            $total_days = count(SimpleBookings::create_date_range_array(
                $data["StartDate"],
                $data["EndDate"]
            ));

            $total_booked_places = SimpleBookings::get_total_booked_spaces(
                $data["StartDate"],
                $data["EndDate"],
                $object->ID
            );

            // If spaces are available, then we need to setup our shopping cart,
            // else  return with an error
            if ($total_booked_places + $data["Quantity"] <= $object->AvailablePlaces) {
                if($object->TaxRateID && $object->TaxRate()->Amount) {
                    $tax_rate = $object->TaxRate()->Amount;
                } else {
                    $tax_rate = 0;
                }

                $customisations[] = array(
                    "Title" => _t("SimpleBookings.StartDate", "Start Date"),
                    "Value" => $data["StartDate"],
                    "Price" => 0
                );

                $customisations[] = array(
                    "Title" => _t("SimpleBookings.EndDate", "End Date"),
                    "Value" => $data["EndDate"],
                    "Price" => 0
                );

                $customisations[] = array(
                    "Title" => _t("SimpleBookings.NoOfDays", "Number of Days"),
                    "Value" => $total_days,
                    "Price" => ($object->Price * $total_days) - $object->Price
                );

                $item_to_add = array(
                    "Key" => $object->ID . ':' . base64_encode(json_encode($customisations)),
                    "Title" => $object->Title,
                    "Content" => $object->Content,
                    "BasePrice" => $object->Price,
                    "TaxRate" => $tax_rate,
                    "Image" => $object->Images()->first(),
                    "StockID" => $object->StockID,
                    "CustomisationArray" => $customisations,
                    "ID" => $object->ID,
                    "Weight" => $object->Weight,
                    "ClassName" => $object->ClassName,
                    "Locked" => SimpleBookings::config()->lock_cart,
                    "Deliverable" => SimpleBookings::config()->allow_delivery,
                    "Stocked" => false
                );
                
                // Try and add item to cart, return any exceptions raised
                // as a message
                try {
                    $cart->add($item_to_add, $data['Quantity']);
                    $cart->save();
                    
                    $message = _t('Commerce.AddedItemToCart', 'Added item to your shopping cart');
                    $message .= ' <a href="'. $cart->Link() .'">';
                    $message .= _t('Commerce.ViewCartNow', 'View cart now');
                    $message .= '</a>';

                    $this->setSessionMessage(
                        "success",
                        $message
                    );
                } catch(ValidationException $e) {
                    $form->sessionMessage(
                        $e->getMessage(),
                        "bad"
                    );
                } catch(Exception $e) {
                    $form->sessionMessage(
                        $e->getMessage(),
                        "bad"
                    );
                }
            } else {
                $form->sessionMessage(
                    _t("SimpleBookings.NoSpacesAvailable", "There are not enough spaces available for this date"),
                    "bad"
                );
            }
        } else {
            $form->sessionMessage(
                _t("Commerce.ThereWasAnError", "There was an error"),
                "bad"
            );
        }

        return $this->redirectBack();
    }
}