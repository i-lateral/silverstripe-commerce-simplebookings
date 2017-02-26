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
     * Special action to actualise this booking (so it's items are registered)
     *
     */
    public function doBook($data, $form)
    {
        $record = $this->record;

        /*if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($record);
        
        $record->Disabled = 0;
        $record->write();
        $this->gridField->getList()->add($record);*/

        $message = sprintf(
            _t('Catalogue.Enabled', 'Enabled %s %s'),
            $this->record->singular_name(),
            '"'.Convert::raw2xml($this->record->Title).'"'
        );
        
        $form->sessionMessage($message, 'good');
        return $this->edit(Controller::curr()->getRequest());
    }
}
