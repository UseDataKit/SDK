# Creating DataViews

<div class="responsive-iframe-container">
    <iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/IjqWRIL9i6A?si=ZrqXKemGLN1G4caq" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
</div>

## Start with the default view type

To create a `DataView` you need to call any of the named constructors available:

- `DataView::table()` - Creates a DataView with a `table` view type.
- `DataView::list()` - Creates a DataView with a `list` view type.
- `DataView::grid()` - Creates a DataView with a `grid` view type.

The view type used will be the default view type, but you can add support for other view types by declaring the other
view types that are supported:

```php
$dataview = DataView::table( ... )->supports( View::Grid(), View::List() );
```

The order in which support is added is the order that the view types will appear in the view type switcher.

:::note

Since every view type has different required parameters, the `new DataView()` creation is *not* available. The
named constructors will have all the required parameters, and make for a fluent and expressive API.

:::

## Apply settings

A DataView has a public API for applying some default settings to the component.

### Primary Field

A DataView can have a primary field. This field is highlighted in each layout type; and can not be hidden. To set this
primary field, you call the `primary_field( Field $field )` method on the instance, and reference the field object you
want to use.

```php
$fields = [
    TextField::create( 'author' , 'Author' ),
    $title_field => TextField( 'title', 'Title' ), // Store the field instance in a separate variable.
];

$dataview = DataView::table( 'table', $data_source, $fields )
    ->primary_field( $title_field ); // Set the reference to the field instance.
```

### Media Field

Both the `Grid` and `List` layout try to show a media object for every result. By default, it will look for the first 
media field available on the fields; but you can also specify a specific field in case you have multiple images.

```php
$dataview = DataView::table( ... )
    ->media_field( $media_field ); // Set the reference to the field instance.
```

### Searching

By default, search is enabled on the DataView component. To disable this, you can call the `disable_search()` method on
the instance.

```php
$dataview = DataView::table( ... )->disable_search();
```

It is also possible to (re-enable and) set the initial search value on the instance by calling
the `search( string $search)` method. You can use this for example to use a query-string parameter from the current url.

```php
// A dataview that prefills the search value with the value from `?search=search+string`.
$dataview = DataView::table( ... )->search( $_GET['search'] ?? '');
```

### Pagination & Sorting

As a DataView can paginate and sort by default, you can also provide an initial sorting and pagination.

#### Sorting by field

A DataView can be sorted by calling the `sort( ?Sort $sort )` method. It requires a `Sort` object to be passed,
or `null` to remove any prior set sorting. The `Sort` object can be created with 2 named constructors:

- `Sort::asc( string $field )` Creates a ascending `Sort` object on a single field.
- `Sort::desc( string $field )` Creates a descending `Sort` object on a single field.

```php
// Apply a default descending sort on `date_created`.
$dataview = DataView::table( ... )->sort( Sort::desc( 'date_created' ) );
```

#### Set pagination defaults

By default, a DataView is paginated with 25 results per page. You can overwrite this in two way:

- Using the WordPress filter-hook `datakit/dataview/pagination/per-page-default` to set a value for all DataViews.
- By using the `paginate( int $per_page, int $page = 1)` method on the DataView.

```php
// Update the default per page amount to 50 results.
add_filter( 'datakit/dataview/pagination/per-page-default', fn( int $per_page ):int => 50 );

// Or set the pagination on the DataView.
$dataview = DataView::table( ... )->paginate( 50, $_GET['page'] ?? 1 );
```

As you can see, you can also optionally provide the current page. This is useful if you want to use a query parameter on
the URL to provide the page number to load.

## View single results

We understand that sometimes you want to show more information for a single result than in possible on a list view. You
want to make your results viewable. This is why we introduced the `->viewable( array $fields, string $label = 'View')`
method on the DataView. Calling this method will add a primary view action on the list item.

As you might have noticed you need to provide an array of fields (the same way as you would on the DataView object), and
an (optional) label. This label is applied on the action button in the UI.

```php
$dataview = DataView::table( ... )
    ->viewable( [
        TextField::create( ... ),
        // Other fields...
    ] );
```

Clicking the `View` action on the UI will open a Modal with all the fields and data for that single item.

By default we use a `<table>` template with the same styles as a DataView applied. But you can replace this template
completely by using the`datakit/dataview/view/template` WordPress filter-hook. This hook receives:

- The current absolute path to the template
- The current DataView object
- A `DataView` item that contains the fields and data for the single result

The hook should return an absolute path to a template file. You can inspect
the [original template](https://github.com/GravityKit/DataKit/blob/main/templates/view/table.php) to get some
inspiration.

> *Tip*: To close the modal using a link or button in your template, you can mark that element with a `data-close-modal`
> attribute.
> ```html
> <button data-close-modal type="button">Close modal</button>
> ```

## Delete a result

In some situations, you might want to be able to delete a result from the list. For this we introduced
the `->deletable( string $label, ?callable $callback = null)` method on the DataView.

:::note

This functionality requires the DataView to have a `MutableDataSource` which `can_delete()`. This data source is
responsible for deleting the results. If the data source can not delete results, calling the `deleteable()` method will
not do anything.

:::

Once the method is called, it will add a primary, destructive "Delete" bulk-action on the results. Clicking this will
perform an AJAX call to the REST API, which in turn will instruct the data source to delete the results by their IDs.
After this action, the current result set on the DataView is refreshed.

You can provide a different `$label` on the method which is used as a label on the action button.

Because this is a destructive action we've added a default confirmation message on the action. To change this message,
or any other option on the action you can use the `$callback` parameter. The callable provided to this parameter will
receive the created `Action` object, and should return an action as well.

```php
use DataKit\DataViews\DataView\Action;

$dataview = DataView::table( ... )
    ->deletable( 'Delete', function ( Action $action ): Action {
        return $action
            ->single() // Make it a single result action, instead of bulk.
            ->confirm( 'Are you sure you want to delete this item?' ); // Set a singular item message.
    });
```

:::note

An `Action` object is immutable. Any method call will return a new instance with the changes applied. This is
why we return the result of the `confirm()` method, instead of calling the methods and then return the `$action`
variable. That would return the exact instance that was provided, causing no changes to be applied.

:::
