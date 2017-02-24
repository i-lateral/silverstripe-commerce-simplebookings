<?php

class BookingAdmin extends ModelAdmin
{
    private static $url_segment = 'bookings';

    private static $menu_title = 'Bookings';

    private static $menu_priority = 4;

    private static $managed_models = array(
        'Booking' => array('title' => 'Bookings')
    );

    private static $model_importers = array(
        'Booking' => 'CSVBulkLoader',
    );

    public $showImportForm = array('Booking');

    /**
	 * @return Form
	 */
	public function SearchForm() {
        $form = parent::SearchForm();
        $fields = $form->Fields();
        $query = $this->getRequest()->getVar("q");

        $fields->replaceField(
            "q[Start]",
            $start_field = DateField::create("q[StartDate]", _t("SimpleBookings.StartDate", "Start Date"))
                ->setConfig('dateformat', 'yyyy-MM-dd')
                ->setConfig('showcalendar', true)
        );

        $fields->replaceField(
            "q[End]",
            $end_field = DateField::create("q[EndDate]", _t("SimpleBookings.EndDate", "End Date"))
                ->setConfig('dateformat', 'yyyy-MM-dd')
                ->setConfig('showcalendar', true)
        );

        if (is_array($query) && array_key_exists("StartDate", $query)) {
            $start_field->setValue($query["StartDate"]);
        }

        if (is_array($query) && array_key_exists("EndDate", $query)) {
            $end_field->setValue($query["EndDate"]);
        }

		$this->extend('updateSearchForm', $form);

		return $form;
	}

    public function getList()
    {
        $list = parent::getList();

        $filter = array();
        
        // Perform complex filtering
        if ($this->modelClass == 'Booking') {
            $query = $this->getRequest()->getVar("q");

            // If a start date and end date are set, filter all dates
            if (is_array($query) && array_key_exists("StartDate", $query) && array_key_exists("EndDate", $query)) {
                // If both dates are the same, we can assume that it is a one day booking
                if ($query["StartDate"] == $query["EndDate"]) {
                    $filter["Start:LessThanOrEqual"] = $query["StartDate"];
                    $filter["End:GreaterThanOrEqual"] = $query["EndDate"];
                } else {
                    $filter["Start:GreaterThanOrEqual"] = $query["StartDate"];
                    $filter["End:LessThanOrEqual"] = $query["EndDate"];
                }
            }
        }

        if (count($filter)) {
            $list = $list->filter($filter);
        }
        
        $this->extend('updateList', $list);

        return $list;
    }
}