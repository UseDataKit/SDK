# Using fields

A DataView is comprised of fields and data. The data is provided by a [DataSource](../create-a-data-source.md), and the
fields a provided by you. In this chapter we'll explore what a field is, and how you can create your own.

## What are fields?

Fields in a DataView are rendered differently depending on the Layout. For a Table view, the fields are shown as columns
on a table. However, for every layout type; the registration of the fields is the same.

Currently DataKit provides the following field types:

- `TextField`: Renders the value as plain text. Tags are stripped, and no HTML is parsed.
- `HtmlField`: Renders the value as HTML.
- `DateTimeField`: Renders the value as a date according to a provided format.
- `EnumField`: Renders the output based on a fixed set op possible values.
- `ImageField`: Renders the value as a `<img />` tag.
- `LinkField`: Renders the value as a link.
- `StatusIndicator`: Renders the value as a status indicator (active/inactive, or with different states).

DataKit provides an expressive way to instantiate the fields you want to use. Please not that the fields are immutable,
and every method call will produce a different instance.

Every field is (and should be) extended from the abstract `Field` class. This class provides an API that is valid
for every field type.

## Creating a field instance

*In this example we'll focus on a `TextField` as it is the most basic field, but it should be valid for most fields.*

To provide a fluent API, a field is created by the named constructor `Field::create(string $id, string $label)`. You
need to call this method on the specific field class; calling `Field::create` will result in an error, as the `Field`
class is abstract and cannot be instantiated.

As you might have noticed, there is an `id` and a `label` for every field. The `id` is a reference to the field name on
the DataSource the DataView uses. Let's assume an `ArrayDataSource` with a `name` and`date_of_birth` key. To create a
text field for the name you need to call:

```php
use DataKit\DataViews\Field\TextField;

$name = TextField::create( 'name', 'Full name' );
```

This will create a field for the `name` field, with a label of `Full name`.

> *Note:* Please see the documentation for the specific field types, as for some fields there are more required
> parameters.


## TODO: Modify field instances

- `->sortable()` (default)
- `->not_sortable()`
- `->always_visible()`
- `->hidable()` (default)
- `->hidden()`
- `->visible()` (default)
- `->primary()`
- `->secondary()` (default)
- `->callback( ?callable $callback )` Manipulate the value before it is handed over to the renderer.
- `->default_value( ?string $default_value )`
- `->default_value( ?string $default_value )`
- 
