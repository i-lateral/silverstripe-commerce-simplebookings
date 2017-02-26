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
        'ItemEditForm'
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
                        _t('SimpleBookings.ViewOrder', 'ViewOrder')
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
    public function doViewOrder($data, $form)
    {
        $record = $this->record;
        $controller = Controller::curr();
        
        return $controller->redirect($record->CMSOrderLink());
    }
}
