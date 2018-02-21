<?php

class ResourceAllocation extends DataObject
{
    private static $db = array(
        'Start' => 'SS_DateTime',
        'End'   => 'SS_DateTime',
        'AllocateAll' => 'Boolean'   
    );

    private static $many_many = array(
        'Resources' => 'BookableProduct'
    );

    private static $many_many_extraFields = array(
        'Resources' => [
            'Quantity' => 'Int',
            'AllocateAll' => 'Boolean'
        ]
    );


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Setup calendars on date fields
        $start_field = $fields->dataFieldByName("Start");
        $end_field = $fields->dataFieldByName("End");

        if ($start_field && $end_field) {
            $start_field
                ->getDateField()
                ->setConfig("showcalendar", true);

            $end_field
                ->getDateField()
                ->setConfig("showcalendar", true);
        }

        // Add editable fields to manage quantity
        $resources_field = $fields->dataFieldByName("Resources");

        if ($resources_field) {
            $config = $resources_field->getConfig();

            $editable_cols = new GridFieldEditableColumns();
            $editable_cols
                ->setDisplayFields(array(
                    'Title' => array(
                        'field' => 'ReadonlyField',
                        'title' => _t("SimpleBookings.Title", "Title")
                    ),
                    'AvailablePlaces' => array(
                        'field' => 'ReadonlyField',
                        'title' => _t("SimpleBookings.TotalPlaces", "total Places")
                    ),
                    'Quantity' => array(
                        'field' => 'NumericField',
                        'title' => _t("SimpleBookings.NumbertoAllocate", "Number to Allocate")
                    ),
                    'AllocateAll' => array(
                        'field' => 'CheckBoxField',
                        'title' => _t("SimpleBookings.AllocateAll", "Allocate All")
                    )
                ));


            $config
                ->removeComponentsByType("GridFieldAddNewButton")
                ->removeComponentsByType("GridFieldDeleteAction")
                ->removeComponentsByType("GridFieldEditButton")
                ->removeComponentsByType("GridFieldDetailForm")
                ->removeComponentsByType("GridFieldDataColumns")
                ->addComponent($editable_cols)
                ->addComponent(new GridFieldDeleteAction(true));
            
            $fields->removeByName("Resources");
            
            $fields->addFieldToTab(
                "Root.Main",
                $resources_field
            );
        }

        return $fields;
    }
}