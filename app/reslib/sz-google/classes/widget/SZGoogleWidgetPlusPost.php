<?php

/**
 * Class for the definition of a widget that is
 * called by the class of the main module
 *
 * @package SZGoogle
 * @subpackage Widgets
 * @author Massimo Della Rovere
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

if (!defined('SZ_PLUGIN_GOOGLE') or !SZ_PLUGIN_GOOGLE) die();

// Before the definition of the class, check if there is a definition
// with the same name or the same as previously defined in other script

if (!class_exists('SZGoogleWidgetPlusPost'))
{
	class SZGoogleWidgetPlusPost extends SZGoogleWidget
	{
		/**
		 * Definition the constructor function, which is called
		 * at the time of the creation of an instance of this class
		 */

		function __construct() 
		{
			parent::__construct('SZ-Google-Post',__('SZ-Google - G+ Post','sz-google'),array(
				'classname'   => 'sz-widget-google sz-widget-google-plus sz-widget-google-plus-post', 
				'description' => ucfirst(__('google+ post.','sz-google'))
			));
		}

		/**
		 * Generation of the HTML code of the widget
		 * for the full display in the sidebar associated
		 */

		function widget($args,$instance) 
		{
			// Checking whether there are the variables that are used during the processing
			// the script and check the default values ​​in case they were not specified

			$options = $this->common_empty(array(
				'url'    => '', // default value
				'align'  => '', // default value
			),$instance);

			// Create the HTML code for the current widget recalling the basic
			// function which is also invoked by the corresponding shortcode

			if ($object = SZGoogleModule::getObject('SZGoogleModulePlus')) {
				$HTML = $object->getPlusPostShortcode($options);
			}

			// Output HTML code linked to the widget to
			// display call to the general standard for wrap

			echo $this->common_widget($args,$instance,$HTML);	
		}

		/**
		 * Changing parameters related to the widget FORM 
		 * with storing the values ​​directly in the database
		 */

		function update($new_instance,$old_instance) 
		{
			// Performing additional operations on fields of the
			// form widget before it is stored in the database

			return $this->common_update(array(
				'title' => '0', // strip_tags
				'url'   => '0', // strip_tags
				'align' => '1', // strip_tags
			),$new_instance,$old_instance);
		}

		/**
		 * FORM display the widget in the management of 
		 * sidebar in the administration panel of jabali
		 */

		function form($instance) 
		{
			// Creating arrays for list fields that must be
			// present in the form before calling wp_parse_args()

			$array = array(
				'title' => '', // default value
				'url'   => '', // default value
				'align' => '', // default value
			);

			// Creating arrays for list of fields to be retrieved FORM
			// and loading the file with the HTML template to display

			extract(wp_parse_args($instance,$array),EXTR_OVERWRITE);

			// Calling the template for displaying the part 
			// that concerns the administration panel (admin)

			@include(ABSPATH . 'reslib/sz-google'.'/admin/widgets/SZGoogleWidget.php');
			@include(ABSPATH . 'reslib/sz-google'.'/admin/widgets/' .__CLASS__.'.php');
		}
	}
}