<?php

class BookingAdmin extends ModelAdmin
{
    private static $url_segment = 'bookings';

    private static $menu_title = 'Bookings';

    private static $menu_priority = 4;

    /**
     * The default start time used for filtering
     *
     * @var string
     */
    private static $default_start_time = "00:00";

    /**
     * The default end time used for filtering
     *
     * @var string
     */
    private static $default_end_time = "23:59";

    private static $managed_models = array(
        'Booking' => array('title' => 'Bookings'),
        'ResourceAllocation' => array('title' => 'Resource Allocations')
    );

    private static $model_importers = array(
        'Booking' => 'CSVBulkLoader',
    );

    public $showImportForm = array('Booking');

    /**
	 * @return Form
	 */
	public function SearchForm()
    {
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

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        $gridField = $form->Fields()->fieldByName($this->modelClass);
        $config = $gridField->getConfig();

        // Alterations for Hiarachy on product cataloge
        if ($this->modelClass == 'Booking') {
            $alerts = array(
				'OverBooked' => array(
					'comparator' => 'equal',
					'patterns' => array(
						'1' => array(
							'status' => 'alert',
							'message' => _t("SimpleBookings.OverBookedResource", 'This has an Over Booked resource'),
						),
					)
				)
			);

            $config
                ->removeComponentsByType("GridFieldDetailForm")
                ->addComponent(new GridFieldRecordHighlighter($alerts))
                ->addComponent(new BookingDetailForm());
        }

        $this->extend('updateEditForm', $form);

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
            if (is_array($query)) {
                $start_date = null;
                $end_date = null;

                if (array_key_exists("StartDate", $query) && $query["StartDate"]) {
                    $start_date = new DateTime($query["StartDate"] . " " . $this->config()->default_start_time);
                }

                if (array_key_exists("EndDate", $query) && $query["EndDate"]) {
                    $end_date = new DateTime($query["EndDate"] . " " . $this->config()->default_end_time);
                } elseif ($start_date) {
                    $end_date = new DateTime($query["StartDate"] . " " . $this->config()->default_end_time);
                }

                // If both dates are the same, we can assume that it is a one day booking
                if ($start_date && $end_date) {
                    if ($start_date->format("Y-m-d") == $end_date->format("Y-m-d")) {
                        
                        $list = $list
                            ->exclude("End:LessThan", $start_date->format("Y-m-d H:i:s"))
                            ->exclude("Start:GreaterThan", $end_date->format("Y-m-d H:i:s"));
                    } else {
                        $list = $list->filter(array(
                            "Start:GreaterThanOrEqual" => $start_date->format("Y-m-d H:i:s"),
                            "End:LessThanOrEqual" => $end_date->format("Y-m-d H:i:s")
                        ));
                    }
                }
            }
        }
        
        $this->extend('updateList', $list);

        return $list;
    }
}