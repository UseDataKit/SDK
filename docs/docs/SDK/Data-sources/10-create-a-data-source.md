---
title: Create a data source
sidebar: auto
---
# Create a data source

DataKit supports different data sources. Out of the box it provides sources for different form plugins like Gravity
Forms, a [CSV data source](20-csv-data-source.md) and an [in-memory array data source](15-array-data-source.md). But
you can also create your own data source, and hook it up to a DataView.

## Read-only data source

There are two types of data sources, read-only and mutable. First lets look at the read-only data source, as it is the
basis for the mutable data source as well.

To get started, you can either create a new class that implements the `DataKit\DataViews\Data\DataSource` interface, or
better yet; create a new class that *extends* the `DataKit\DataViews\Data\BaseDataSource` abstract class. This class
already implements 3 methods, namely: `filter_by`, `sort_by` and `search_by`. These methods store their respective
values on the class to be used by the other methods later on.

### Identify the data source type

A data source needs to be able to be differentiated from other sources types. For this purpose we implement the `id`
method, which returns a unique and consistent identifier (string) for your data source. The `CsvDataSource` for example
returns the value `csv-{filename}`, where the `filename` differentiates between CsvDataSource instances.

```php
public function id() : string {
    return 'custom';
}
```

### Retrieving (paginated) results

A data source mostly revolves around 2 methods. First there
is `get_data_ids( int $limit = 20, int $offset = 0 ) : array`.
This method should return an array of strings, that represent unique ID's for every result. It should also take into
account the `$limit` and `$offset`, as well as any filters, sorting and search query stored by the aforementioned
methods.

Secondly there is `get_data_by_id( string $id ) : array` which should return the data for the result based on this ID.
If the result is not found, the method should throw a `DataNotFoundException`.

The reason for the separation of these methods is so that every data source is able to return the data in the same way.
Even if it uses an API that has a separate endpoint to retrieve the data.

The array `get_data_by_id()` returns should be a key/value-pair, where both the key and the value are a `string`.
The `key` is what is referenced by a Field ID. For example, if we want to show a field with a name of `email`, it will
look for a `key` by the name of `email` on the array.

> **Tip:** The methods `get_data_ids()` and `get_data_by_id()` are usually called in rapid succession,
> because `get_data_by_id()` will be called for every result of `get_data_ids()`. If the data source is able to retrieve
> all the results with a single query, it could be wise to retrieve all these results beforehand on the `get_data_ids()`
> call, and micro cache these results in memory. That way the `get_data_by_id()` method does not need te perform a query
> per ID.

### Calculating pagination results

To be able to show the pagination options based on the limit and offset, we need to know the total amount of records.
For this the `public function count(): int` should return this amount, while also taking into consideration the applied
filters and search query.

### Filtering & sorting results

As we mentioned, the `filter_by`, `sort_by` and `search_by` methods (should) keep track of these values, to be used
while retrieving the result ids in `get_data_ids()`.

#### Filters

If filters are applied, you can use the `Filters` object to filter your results. The `Filters` object is an iterable
class that returns `Filter` objects, which in turn can be turned into an array. Alternatively the `Filters` object can
also be turned into a usable array. In both cases you call the `to_array` method on the object.

```php
private function data() : array {
    $filters = $this->filters->to_array();
    //or
    foreach( $filters as $filter) {
        $filter_array = $filter->to_array();
    }
}
```

The `Filter` array contains the following keys:

- `field` a `string` representing the field name to filter against.
- `value` which is either a string (for `is` and `isNot` operations), or an array of strings for the other operations.
- `operation` a `string` representing the filter operation type:
    - `is` should return a result where the `field` equals `value`
    - `isNot` should return a result where the `field` does not equal `value`
    - `isAny` should return a result where the field is any of the provided values
    - `isAll` should return a result where the field is has all of the provided values
    - `isNone` should return a result where the field is none of the provided values
    - `isNotAll` should return a result where the field is does not have all of the provided values

#### Searching

If the `search` parameter has a value, the results should be filtered based on this value. How you implement this is up
to you. Fuzzy search results are allowed, even encouraged. But if you only want to allow strict values, that is fine.

#### Sorting

The sorting of the data source is provided on the `sort_by` method. It should change the order of the ID's returned
by `get_data_ids()`. The `Sort` object can also be transformed into an array by calling the `to_array()` method. This
array contains the following keys:

- `field` a `string` representing the field key to sort by
- `direction` either `ASC` (ascending) or `DESC` (descending)

```php
private function data() : array {
    // ...
    $sorting = $this->sort->to_array(); 
}
```

### Retrieving data source fields

In order to know the available fields for a data source, it needs to provide these fields from the `get_fields()`
method. These results are (going to be) used by the DataView Builder UI. Therefor the result should be a key/value-pair
of the field name, and a human-readable label.

```php
public function get_fields() : array {
    return [
        'email' => 'Email Address',
        'name' => 'Full Name',
    ];
}
```

## Mutable data source

As DataKit matures, more features will be added. One of the first features we did want to address right away, is the
ability to delete a result. For this we introduced the `MutableDataSource`. As the name suggests, this data source can
apply changes on its data; it is [mutable](https://en.wiktionary.org/wiki/mutable).

### Deleting a result

After implementing the `MutableDataSource` on the data source class, you need to implement the
`public function delete_data_by_id( string ...$ids ) : void;` method. As you can see it allows you to provide multiple
id's to be removed, via the spread operator (`...`). This means the method is able to delete a single result, as well as
multiple results at once (depending on the backing implementation).

Notice that the return type is `void`. This means DataKit will assume the deletion was successful, unless it encounters
a `DataNotFoundException`.

```php
use DataKit\DataViews\Data\Exception\DataNotFoundException;

public function delete_data_by_id( string ...$ids ) : void {
    try {
        DataSourceApi::delete_by_ids( $ids );
    } catch ( NotFoundException $e ){
        throw new DataNotFoundException( $this, $e->getMessage(), 404, $e );
    }
    
    // or
    foreach ( $ids as $id ) {
        if (! DataSourceApi::has( $id )) {
            throw new DataNotFoundException( $this, $e->getMessage(), 404, $e );
        }
        
        DataSourceApi::delete_by_id($id);
    }
}
```

Notice how in this example we provide a reference to the current data source (`$this`) on the `DataNotFoundException`.
This is useful for logging purposes, for example.

## Composing a data source

By default, DataKit only provides either `final` or `abstract` classes. This makes it easier for us to add new features,
and change implementation details; without introducing breaking changes to the end user. We believe in composition over
inheritance, which is why we provide interfaces and a `DataSourceDecorator` to aid in that process.

We understand that you might have a data source that only has a fixed set of data, and does not support pagination
out-of-the-box. This means you could use the `ArrayDataSource` to add most of the functionality, except for the data.

Here is an example of how you could create such a data source with composition.

```php
use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\DataSourceDecorator;

// Extend the abstract datasource decorator to proxy most methods. 
final class CustomDataSource extends DataSourceDecorator {
	/**
	 * Property to memoize the inner data source.
	 * @var ArrayDataSource
	 */
	private ArrayDataSource $inner;

	public function __construct( ...$arguments ) {
		// Create an instance with the necessary arguments and dependencies, but don't retrieve the results yet!
		// This instance might only be used to show the name on the UI, or not even used on the current page,
		// so retrieving results can be premature here.
	}

	public function id() : string {
		return 'custom';
	}

	protected function decorated_datasource() : DataSource {
		// We already instantiated the
		if ( isset( $this->inner ) ) {
			return $this->inner;
		}

		// Retrieve the results
		$results = get_results_from_api_call();

		// Instantiate and memoize the inner data source for future calls.
		return $this->inner = new ArrayDataSource( $this->id(), $results );
	}
}
```
