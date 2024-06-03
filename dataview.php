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


use DataKit\DataView\Data\GravityFormsDataSource;
use DataKit\DataView\DataView\DataView;
use DataKit\DataView\DataView\Filter;
use DataKit\DataView\DataView\Filters;
use DataKit\DataView\DataView\JsonDataViewRenderer;
use DataKit\DataView\DataView\Operator;
use DataKit\DataView\DataView\Sort;
use DataKit\DataView\Field\EnumField;
use DataKit\DataView\Field\HtmlField;
use DataKit\DataView\Field\LinkField;
use DataKit\DataView\Field\TextField;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require_once 'vendor/autoload.php';

add_action( 'gform_loaded', static function () : void {
	$data_source = new GravityFormsDataSource( 1 );
	$view        = DataView::table(
		[
			EnumField::create( 'status', 'Status', [ 'active' => 'Active', 'trash' => 'Trash', 'spam' => 'Spam' ] )
			         ->filterable_by( Operator::is )
			         ->primary(),
			TextField::create( '2', 'Email' )
			         ->filterable_by( Operator::is, Operator::isNot )
			         ->default_value( 'Not provided' )
			         ->secondary(),
		],
		$data_source,
		Sort::asc( 'date_created' ),
		Filters::of(
			Filter::is( '1.3', 'test' ),
		),
	);

	$renderer = new JsonDataViewRenderer();
	echo $renderer->render( $view );
	exit;
} );
