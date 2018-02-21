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
        $calendar = BookingCalendarField::create("Calendar","Calendar",null,$object);
        $calendar->setForm($form);

        $fields->push($calendar);

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
        $time_start = strtotime($data["Calendar"]['StartDate']);
        $time_end = strtotime($data["Calendar"]['EndDate']);
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
            // Check if we are trying to book less places than allowed
            if ($object->MinimumPlaces && $data["Quantity"] < $object->MinimumPlaces) {
                $form->addErrorMessage(
                    "Quantity",
                    _t(
                        "SimpleBookings.NotEnoughPlacesError",
                        "You must book a minimum of {value} places",
                        'Message generated when user tries to book to few places',
                        array('value' => $object->MinimumPlaces)
                    ),
                    "bad"
                );

                return $this->redirectBack();
            }

            $total_time = count(SimpleBookings::create_date_range_array(
                $data["Calendar"]["StartDate"],
                $data["Calendar"]["EndDate"],
                $object->PricingPeriod
            ));

            if ($resources = $object->Resources()) {
                $resource = $resources->First();
            } else {

            }

            // If spaces are available, then we need to setup our shopping cart,
            // else  return with an error
            if (!$resource || ($resource && $resource->isAvailable($data["Calendar"]["StartDate"],$data["Calendar"]["EndDate"],$data["Quantity"]))) {
                if($object->TaxRateID && $object->TaxRate()->Amount) {
                    $tax_rate = $object->TaxRate()->Amount;
                } else {
                    $tax_rate = 0;
                }

                $customisations[] = array(
                    "Title" => _t("SimpleBookings.StartDate", "Start Date"),
                    "Value" => $data["Calendar"]["StartDate"],
                    "Price" => 0
                );

                $customisations[] = array(
                    "Title" => _t("SimpleBookings.EndDate", "End Date"),
                    "Value" => $data["Calendar"]["EndDate"],
                    "Price" => 0
                );

                $customisations[] = array(
                    "Title" => _t("SimpleBookings.LengthOfTime", "Length of Time"),
                    "Value" => $total_time,
                    "Price" => ($object->Price * $total_time) - $object->Price
                );

                $item_to_add = array(
                    "Key" => $object->ID . ':' . base64_encode(json_encode($customisations)),
                    "Title" => $object->Title,
                    "Content" => $object->Content,
                    "BasePrice" => $object->Price,
                    "TaxRate" => $tax_rate,
                    "Image" => $object->Images()->first(),
                    "StockID" => $object->StockID,
                    "Customisation" => $customisations,
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