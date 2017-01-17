<?php

class BookingDateField extends DateField
{

	/**
	 * Tell this field to use DateField_View_JQuery to render this fields
	 * JS and CSS instead of the custom BookingDateField_View_JQuery class
	 *
	 * @var boolean
	 * @config
	 */
	private static $use_default_view = false;

    public function FieldHolder($properties = array())
	{
		if ($this->getConfig('showcalendar')) {
			// TODO Replace with properly extensible view helper system
			if ($this->config()->use_default_view) {
				$d = DateField_View_JQuery::create($this);
			} else {
				$d = BookingDateField_View_JQuery::create($this);
			}

			if(!$d->regionalSettingsExist()) {
				$dateformat = $this->getConfig('dateformat');

				// if no localefile is present, the jQuery DatePicker
				// month- and daynames will default to English, so the date
				// will not pass Zend validatiobn. We provide a fallback
				if (preg_match('/(MMM+)|(EEE+)/', $dateformat)) {
					$this->setConfig('dateformat', $this->getConfig('datavalueformat'));
				}
			}
			$d->onBeforeRender();
		}
		$html = TextField::FieldHolder();

		if(!empty($d)) {
			$html = $d->onAfterRender($html);
		}
		return $html;
	}

	function SmallFieldHolder($properties = array())
	{
		if ($this->config()->use_default_view) {
			$d = DateField_View_JQuery::create($this);
		} else {
			$d = BookingDateField_View_JQuery::create($this);
		}
		
		$d->onBeforeRender();
		$html = TextField::SmallFieldHolder($properties);
		$html = $d->onAfterRender($html);
		return $html;
	}
}

/**
 * Preliminary API to separate optional view properties
 * like calendar popups from the actual datefield logic.
 *
 * Caution: This API is highly volatile, and might change without prior deprecation.
 *
 * @package framework
 * @subpackage forms
 */
class BookingDateField_View_JQuery extends DateField_View_JQuery {

    /**
	 * @param String $html
	 * @return
	 */
	public function onAfterRender($html)
    {
		if($this->getField()->getConfig('showcalendar')) {
			Requirements::javascript(SSViewer::get_theme_folder() . '/bower_components/jquery/dist/jquery.min.js');
			Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
			Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-ui/jquery-ui.js');

			// Include language files (if required)
			if ($this->jqueryLocaleFile){
				Requirements::javascript($this->jqueryLocaleFile);
			}

			Requirements::javascript(FRAMEWORK_DIR . "/javascript/DateField.js");
		}

		return $html;
	}
}