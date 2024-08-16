---
title: Filters
sidebar: auto
---

# Filters

When creating a `DataView` you can apply a set of default filters. These filters will pre-fill the UI and are also used
by the REST endpoint to retrieve the filtered results.

## Create a set of filters

A `Filter` is a value object that consists of a field id, an operator and a value. The `Filter` class has an expressive
API of creating these objects. It has a static constructor for any valid `Operator` type.

- `Filter::is( string $field, mixed $value )` Creates an `is` filter with a single value.
- `Filter::isNot( string $field, mixed $value )` Creates an `isNot` filter with a single value.
- `Filter::isAny( string $field, array $value )` Creates an `isAny` filter with multiple values.
- `Filter::isAll( string $field, array $value )` Creates an `isAll` filter with multiple values.
- `Filter::isNone( string $field, array $value )` Creates an `isNone` filter with multiple values.
- `Filter::isNotAll( string $field, array $value )` Creates an `isNotAll` filter with multiple values.

For example; to create a filter for field `category` that should have any of the categories: `movies` & `music` you call
the following code:

```php
use DataKit\DataViews\DataView\Filter;

$category_filter = Filter::isAny( 'category', [ 'movies', 'music' ] );
```

A `DataView` object requires a `Filters` collection of `Filter` objects passed along to the `$filters` parameter on the
named constructor. The `Filters::of( Filter ... $filters )` method creates this collection, which you can pass an
unlimited amount of filters.

```php
use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;

$filters = Filters::of(
    Filter::is( 'category', 'music' ),
    Filter::isAny( 'theme', [ 'pop','rock' ] )
);

$dataview = DataView::table( 'my-dataview', $datasource, $fields, null, $filters );
```
