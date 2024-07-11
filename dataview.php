<?php

/**
 * Plugin Name:         DataView
 * Plugin URI:          https://www.gravitykit.com
 * Version:             0.1.0
 * Author:              GravityKit
 * Author URI:          https://www.gravitykit.com
 * Text Domain:         dk-datakit
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 */

use DataKit\DataViews\DataView\ArrayDataViewRepository;
use DataKit\DataViews\DataViewPlugin;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require_once 'vendor/autoload.php';

const DATAVIEW_PLUGIN_PATH = __FILE__;

// Initialize the plugin.
try {
	DataViewPlugin::get_instance(
		new ArrayDataViewRepository(),
	);
} catch ( \Throwable $e ) {
	// Todo: log errors somewhere.
}
