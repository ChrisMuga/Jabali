<?php

/**
 * Script to implement the HTML code shared with widgets 
 * in the function pop-up insert shortcodes via GUI
 *
 * @package SZGoogle
 * @subpackage Admin
 * @author Massimo Della Rovere
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

if (!defined('SZ_PLUGIN_GOOGLE') or !SZ_PLUGIN_GOOGLE) die();

// Creating array to list the fields that must be 
// present in the form before calling wp_parse_args ()

$array = array(
	'title'        => '', // valore predefinito
	'method'       => '', // valore predefinito
	'channel'      => '', // valore predefinito
	'subscription' => '', // valore predefinito
	'text'         => '', // valore predefinito
	'image'        => '', // valore predefinito
	'newtab'       => '', // valore predefinito
);

// Creating arrays to list of fields to be retrieved FORM 
// and loading the file with the HTML template to display

extract(wp_parse_args($instance,$array),EXTR_OVERWRITE);

// Setting any of the default parameters for fields 
// that contain invalid values ​​or inconsistent

if (!ctype_digit($method) or $method == 0) { $method = '1'; }

// Loading ADMIN template for composition using
// shortcodes in many cases the same code Widget

@include(ABSPATH . 'reslib/sz-google'.'/admin/mce/shortcodes/SZGoogleBaseHeader.php');
@include(ABSPATH . 'reslib/sz-google'.'/admin/widgets/SZGoogleWidgetYoutubeLink.php');
@include(ABSPATH . 'reslib/sz-google'.'/admin/mce/shortcodes/SZGoogleBaseFooter.php');