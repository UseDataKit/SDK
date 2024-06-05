<?php

/**
 * Plugin Name:         DataView
 * Plugin URI:          https://www.gravitykit.com
 * Version:             0.1.0
 * Author:              GravityKit
 * Author URI:          https://www.gravitykit.com
 * Text Domain:         gk-dataview
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.html
 */

use DataKit\DataView\DataView\ArrayDataViewRepository;
use DataKit\DataView\DataViewPlugin;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require_once 'vendor/autoload.php';

// Initialize the plugin.
DataViewPlugin::get_instance(
	new ArrayDataViewRepository(),
);
