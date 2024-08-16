# ImageField

The `ImageField` renders the value of a data source as an `<img />` tag.

## Applying field settings

The `ImageField` has a few other settings modifiers on top of
the [default modifiers](10-using-fields.md#applying-field-settings).

- `->size( int width, ?int height = null )` Adds a `width=""` and `height=""` attribute on the tag.
- `->class( string $class )` Adds the provided classes on the `class=""` attribute.
- `->alt( string $alt)` Adds an alt text, with support for merge tags.

Here is a full example:

```php
use DataKit\DataViews\Field\ImageField;

ImageField::create( 'image', 'Image label' )
    ->size( 300, 300 ) // Adds width="300" height="300" to the tag.
    ->alt( 'Image for {name}.') // Adds an alt="Image for person" attribute.
    ->class( 'custom-class-1 custom-class-2'); // Adds `class="custom-class-1 custom-class-2" to the tag.
```

:::tip

The `alt` text can reference the value of another field, by use of "merge tags". In this example we reference
the `name` field by adding `{name}` to the tag. This tag will be replaced by the value from the `name` field.

:::
