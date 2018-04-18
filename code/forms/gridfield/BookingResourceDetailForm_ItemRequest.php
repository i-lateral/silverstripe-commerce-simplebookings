<?php

/**
 * Custom gridfield form that adds default start, end and booked quantity to a
 * booking.
 *  
 * @author ilateral
 */
class BookingResourceDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm'
    );

    /**
     * Customise edit form a booking resource
     * 
     * @return Form
     */
    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $fields = $form->Fields();
        $record = $this->record;

        // If this is overbooked, add a warning
        if (method_exists($record, "getPlacesRemaining") && $record->getPlacesRemaining() < 0) {
            $message = '<p class="message bad"><strong>';
            $message .= _t(
                "SimpleBookings.OverBooked",
                'This resource is Over Booked'
            );
            $message .= '</strong></p>';

            $fields->unshift(
                LiteralField::create("OverBookedMessage", $message)
            );
        }

        // If creating a new record, pre-populate defaults
        if (!$record->exists() && $record->canEdit()) {
            $list = $this->gridField->getList();

            $booking = Booking::get()->byID($list->getForeignID());

            if (isset($booking)) {
                $end = new DateTime($booking->Start);
                $end->modify($record->config()->default_end);

                $fields
                    ->dataFieldByName("Start")
                    ->setValue($booking->Start);

                $fields
                    ->dataFieldByName("End")
                    ->setValue($end->format("Y-m-d H:i:s"));

                $fields
                    ->dataFieldByName("BookedQTY")
                    ->setValue($booking->PartySize);
            }
        }

        $this->extend("updateItemEditForm", $form);

        return $form;
    }
}
