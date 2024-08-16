---
title: Introduction to fields
---
# Using fields

A DataView consists of fields and data. The data is provided by
a [DataSource](../Data-sources/10-create-a-data-source.md),
and the fields are provided by you. In this chapter we'll explore what a field is, and how you can create your own.

## What are fields?

Fields in a DataView are rendered differently depending on the Layout. For a Table view, the fields are shown as columns
on a table. However, for every layout type; the registration of the fields is the same.

Currently, DataKit provides the following field types:

- [`TextField`](11-text-field.md): Renders the value as plain text. Tags are stripped, and no HTML is parsed.
- [`HtmlField`](15-html-field.md): Renders the value as HTML.
- [`DateTimeField`](18-datetime-field.md): Renders the value as a date according to a provided format.
- [`EnumField`](20-enum-field.md): Renders the output based on a fixed set op possible values.
- [`ImageField`](25-image-field.md): Renders the value as a `<img />` tag.
- [`GravatarField`](26-gravatar-field.md): Renders an email address as the [Gravatar](https://gravatar.com/) avatar
  picture.
- [`LinkField`](28-link-field.md): Renders the value as a link.
- [`StatusIndicator`](30-status-indicator-field.md): Renders the value as a status indicator (active/inactive, or with
  different states).

Every field is (and should be) extended from the abstract `Field` class. This class provides an API that is valid
for every field type.

## Creating a field instance

*In this example we'll focus on a `TextField` as it is the most basic field, but it should be valid for most fields.*

To provide a fluent API, a field is created by the named constructor `Field::create( string $id, string $label )`. You
need to call this method on the specific field class; calling `Field::create` will result in an error, as the `Field`
class is abstract and cannot be instantiated.

As you might have noticed, there is an `id` and a `label` for every field. The `id` is a reference to the field name on
the DataSource the DataView uses. Let's assume an `ArrayDataSource` with a `name` key. To create a text field for the
name you need to call:

```php
use DataKit\DataViews\Field\TextField;

$name = TextField::create( 'name', 'Full name' );
```

This will create a field for the `name` field, with a label of `Full name`.

:::note
Please see the documentation for the specific field types, as for some fields there are more required parameters 
on the `create` method (e.g. the `EnumField`).
:::

## Applying field settings

After creating a field, you can finetune the settings for that particular field using a set of methods. Because
a `Field` is **immutable**, every method call will return a new instance with that setting applied. This allows you to
create a field with all your required settings, while being able to pass it around that instance without the
possibility of change.

The following methods are available on any `Field`:

- `->sortable()` (default) Makes entries sortable (Ascending / Descending) on this Field.
- `->not_sortable()` Removes the ability to sort entries on this field.
- `->hideable()` (default) Allows the field to be hidden on the view.
- `->always_visible()` Makes this field always visible on the view; it cannot be hidden.
- `->visible()` (default) Will show the field on initial load.
- `->hidden()` Will not show the field on the initial load.
- `->default_value( ?string $default_value )` Applies a fallback value if the field is empty on the dataset.
- `->callback( ?callable $callback )` Allows changing the value before it is
  rendered. [Read more](#change-value-before-rendering).

## Change value before rendering

Sometimes the value that is recorded on the data sources, is not in the format you want to show on the view. Or the data
is missing, and you want to provide a backup. For this we have two options; a default value and a callback.

### A default value (fallback value)

Whenever the data source does not contain a value for a specific field, you can provide a default value to use instead.
By calling the `->default_value()` method on your field creation, you can provide this value.

```php
$email = TextField::create( 'email', 'Email Address' )->default_value( 'Not provided' );
```

### Changing the value with a callback

In cases where you want to change the formatting of a value you can provide a callback method to the field. This method
receives the field ID, and the entire data item as a parameter. This way you can access all fields, and combine values
into your desired format.

In this example any value longer than 15 characters will be truncated. A result could be `person@gravityk...`.

```php
$email = TextField::create( 'email', 'Email address' )
    ->callback( function ( string $id, array $data ) : string {
        $value = $data[ $id ] ?? ''; // Retrieve the original value for this field.
        if ( strlen( $value ) <= 15 ) {
            return $value;
        }

        // Truncate any value longer than 20 characters.
        return substr( $value, 0, 15 ) . '...';
    } );
```

:::note
The callback function requires a `callable`. This means you can also provide a callable as an array notation, e.g. 
`[ $this, 'my_callback' ]` or even an invokable class instance.
:::

You can even create "fake" fields by combining multiple fields into one.

```php
$name_email = TextField::create( 'name_email', 'Name (Email)' )
    ->callback( function ( string $id, array $data ) : string {
        return sprintf( '%s (%s)', $data['name'] ?? '', $data['email'] ?? '' );
    } );
```

The field `name_email` does not exist on the data set, but the callback function will make sure it will return a value
like `Person (person@gravitykit.com)` on the view.

## Filtering

Fields can be made filterable. These filters are applied by the datasource. Filtering is based around a search query or
a finite set of values. These values and thus the filters are currently only available on
an [`EnumField`](20-enum-field.md).
