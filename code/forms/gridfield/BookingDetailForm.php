<?php
/**
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
            if ($this->record->Order()->exists()) {
                $actions->insertBefore(
                    FormAction::create(
                        'doSyncOrder',
                        _t('SimpleBookings.SyncOrder', 'Sync Order')
                    ),
                    "action_doDelete"
                );
            } else {
                $actions->insertBefore(
                    FormAction::create(
                        'doSyncOrder',
                        _t('SimpleBookings.CreateOrder', 'Create Order')
                    ),
                    "action_doDelete"
                );
            }

            // Add right aligned total field
            $total = $this->record->obj("TotalCost");
            $total_html = '<span class="cms-booking-total ui-corner-all ui-button-text-only">';
            $total_html .= "<strong>Total:</strong> {$total->Nice()}";
            $total_html .= '</span>';

            $actions->push(
                LiteralField::create(
                    "TotalCost",
                    $total_html
                )
            );

        }
        
        $this->extend("updateItemEditForm", $form);
        
        return $form;
    }

    /**
     * Sync the current booking with it's associated order
     */
    public function doSyncOrder($data, $form)
    {
        $record = $this->record;
        $controller = $this->getToplevelController();
        $record->sync();

        $message = _t(
            'SimpleBookings.SyncedOrder',
            'Synced {name} #{number}',
            array(
                'name' => $record->Order()->i18n_singular_name(),
                'number' => $record->Order()->OrderNumber
            )
        );
        
        $form->sessionMessage($message, 'good');

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
}
