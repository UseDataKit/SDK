<?php

use DataKit\DataView\Data\ArrayDataSource;
use DataKit\DataView\DataView\JsonDataViewRenderer;
use DataKit\DataView\DataView\Filter;
use DataKit\DataView\DataView\Filters;
use DataKit\DataView\DataView\Sort;
use DataKit\DataView\Field\HtmlField;
use DataKit\DataView\Field\LinkField;
use DataKit\DataView\Field\TextField;
use DataKit\DataView\DataView\DataView;

require_once 'vendor/autoload.php';

$data_source = new ArrayDataSource(
	'test',
	'Test data',
	[
		'uuid-1' => [
			'title'       => 'First result',
			'author'      => 'Doeke Norg',
			'author_url'  => 'https://doeken.org',
			'description' => 'First description',
		],
		'uuid-2' => [
			'title'       => 'Second result',
			'author'      => 'GravityKit',
			'author_url'  => 'https://gravitykit.com',
			'description' => 'Second description',
		],
		'uuid-3' => [
			'title'       => 'Third result',
			'author'      => 'Aaron Applesteen',
			'description' => 'Third description',
		],
	],
);

$view = DataView::table(
	[
		TextField::create( 'title', 'Title' )->not_hideable(),
		HtmlField::create( 'description', 'Description' )->not_sortable()->secondary(),
		LinkField::create( 'author', 'Author' )->linkToField( 'author_url' ),
		LinkField::create( 'author_url', 'Author URL' )->linkToField( 'author_url' )->hidden(),
	],
	$data_source,
	Sort::asc( 'author' ),
	Filters::of(
		Filter::is( 'author', 'Doeke Norg' ),
	),
);

$renderer = new JsonDataViewRenderer();
echo $renderer->render( $view );
