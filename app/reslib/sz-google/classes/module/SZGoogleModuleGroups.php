<?php

/**
 * Module to the definition of the functions that relate to both the
 * widgets that shortcode, but also filters and actions that the module
 * can integrating with adding functionality into jabali.
 *
 * @package SZGoogle
 * @subpackage Modules
 * @author Massimo Della Rovere
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

if (!defined('SZ_PLUGIN_GOOGLE') or !SZ_PLUGIN_GOOGLE) die();

// Before the definition of the class, check if there is a definition 
// with the same name or the same as previously defined in other script.

if (!class_exists('SZGoogleModuleGroups'))
{
	class SZGoogleModuleGroups extends SZGoogleModule
	{
		/**
		 * Definition of the initial variable array which are
		 * used to identify the module and options related to it
		 */

		function moduleAddSetup()
		{
			$this->moduleSetClassName(__CLASS__);
			$this->moduleSetOptionSet('sz_google_options_groups');
			
			// Definition shortcode connected to the module with an array where you
			// have to specify the name activation option with the shortcode and function

			$this->moduleSetShortcodes(array(
				'groups_shortcode' => array('sz-ggroups',array(new SZGoogleActionGroups(),'getShortcode')),
			));

			// Definition widgets connected to the module with an array where you
			// have to specify the name option of activating and class to be loaded

			$this->moduleSetWidgets(array(
				'groups_widget'    => 'SZGoogleWidgetGroups',
			));
		}
	}

	/**
	 * Loading function for PHP allows developers to implement modules in this plugin.
	 * The functions have the same parameters of shortcodes, see the documentation.
	 */

	@require_once(ABSPATH . 'reslib/sz-google'.'/functions/SZGoogleFunctionsGroups.php');
}