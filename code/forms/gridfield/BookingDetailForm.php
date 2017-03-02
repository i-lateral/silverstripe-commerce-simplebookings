<?php
/**
 * Base class for editing a catlogue object.
 * 
 * Currently allows enabling or disabling of an object via additional buttons
 * added to the gridfield.
 * 
 * NOTE: The object being edited must implement a "Disabled" parameter
 * on it's DB fields.
 *
 * @author ilateral
 */

class BookingDetailForm extends GridFieldDetailForm
{
}

class BookingDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm',

    );

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $form->addExtraClass("cms-booking-form");

		if ($form && $this->record->ID !== 0 && $this->record->canEdit()) {
			$fields = $form->Fields();
			$actions = $form->Actions();

            // Add a button to view this items order (if available)
            if ($this->record->OrderID) {
                $actions->insertBefore(
                    FormAction::create(
                        'doViewOrder',
                        _t('SimpleBookings.ViewOrder', 'View Order')
                    ),
                    "action_doDelete"
                );
            } elseif ($this->record->CustomerID) {
                $actions->insertBefore(
                    FormAction::create(
                        'doCreateOrder',
                        _t('SimpleBookings.CreateOrder', 'Create Order')
                    ),
                    "action_doDelete"
                );
            }

            // Add right aligned total field
            $total_obj = new Currency("TotalCost");
            $total_obj->setValue($this->record->TotalCost);

            $total = '<span class="cms-booking-total ui-corner-all ui-button-text-only">';
            $total .= "<strong>Total:</strong> {$total_obj->Nice()}";
            $total .= '</span>';

            $actions->push(LiteralField::create(
                "TotalCost",
                $total
            ));

        }
        
		$this->extend("updateItemEditForm", $form);
        
        return $form;
    }

    /**
     * Redirect to this record's associated order in the CMS
     *
     */
    public function doCreateOrder($data, $form)
    {
        $record = $this->record;
		$controller = $this->getToplevelController();

        if ($record->CustomerID) {
            $data = array(
                'Company' => $record->Customer()->Company,
                'FirstName' => $record->Customer()->FirstName,
                'Surname' => $record->Customer()->Surname,
                'Address1' => $record->Customer()->Address1,
                'Address2' => $record->Customer()->Address2,
                'City' => $record->Customer()->City,
                'PostCode' => $record->Customer()->PostCode,
                'Country' => $record->Customer()->Country,
                'Email' => $record->Customer()->Email,
                'PhoneNumber' => $record->Customer()->PhoneNumber
            );

            $order = Order::create($data);
            $order->OrderNumber = "";
            
            $order->CustomerID = $record->CustomerID;

            // Write so we can setup our foreign keys
            $order->write();

            // Loop through each booking item and add that to the order
            foreach($record->Products() as $product) {
                $total_days = count(SimpleBookings::create_date_range_array(
                    $record->Start,
                    $record->End
                ));

                $customisations = ArrayList::create();

                // Setup customisations
                $customisations->add(ArrayData::create(array(
                    "Title" => _t("SimpleBookings.StartDate", "Start Date"),
                    "Value" => $record->Start
                )));

                $customisations->add(ArrayData::create(array(
                    "Title" => _t("SimpleBookings.EndDate", "End Date"),
                    "Value" => $record->End
                )));

                $customisations->add(ArrayData::create(array(
                    "Title" => _t("SimpleBookings.NoOfDays", "Number of Days"),
                    "Value" => $total_days
                )));
                
                $order_item = new OrderItem();
                $order_item->Title          = $product->Title;
                $order_item->Customisation  = serialize($customisations);
                $order_item->Quantity       = ($total_days * $product->BookedQTY);
                
                if ($product->StockID) {
                    $order_item->StockID = $product->StockID;
                }

                if ($product->Price) {
                    $order_item->Price = $product->Price;
                }

                if ($product->TaxRate) {
                    $order_item->TaxRate = $product->TaxRate;
                }

                $order_item->write();
                $order->Items()->add($order_item);
            }

            $record->OrderID = $order->ID;
            $record->write();

            $message = _t(
                'SimpleBookings.CreatedOrder',
                'Created {name} #{number}',
                array(
                    'name' => $order->i18n_singular_name(),
                    'number' => $order->OrderNumber
                )
            );
            
            $form->sessionMessage($message, 'good');
        }

        if($this->gridField->getList()->byId($this->record->ID)) {
			// Return new view, as we can't do a "virtual redirect" via the CMS Ajax
			// to the same URL (it assumes that its content is already current, and doesn't reload)
			return $this->edit($controller->getRequest());
		} else {
			// Changes to the record properties might've excluded the record from
			// a filtered list, so return back to the main view if it can't be found
			$noActionURL = $controller->removeAction($data['url']);
			$controller->getRequest()->addHeader('X-Pjax', 'Content');
			return $controller->redirect($noActionURL, 302);
		}
    }

    /**
     * Redirect to this record's associated order in the CMS
     *
     */
    public function doViewOrder($data, $form)
    {
        $record = $this->record;
		$controller = $this->getToplevelController();
        
        return $controller->redirect($record->CMSOrderLink());
    }
}
