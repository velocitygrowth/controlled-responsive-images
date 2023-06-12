<?php
/*
 * Plugin Name: Controlled Responsive Images
 * Description: Take greater control of the `sizes` attribute of images to improve performance.
 * Author: Velocity Growth
 * Author URI: https://velocitygrowth.com/
 * License: MIT
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/ControlledResponsiveImagesPlugin.php';
require_once __DIR__ . '/api.php';

// To initialize the plugin, we just need to call getInstance().
ControlledResponsiveImagesPlugin::getInstance();
