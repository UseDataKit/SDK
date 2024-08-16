# TextField

The TextField is the most basic field that DataKit provides. It will show the value for the field, without any
processing. It does *not* allow HTML; for that you need to switch to the `HtmlField`.

```php
use DataKit\DataViews\Field\TextField;

TextField::create( 'field', 'Label' );
```

## Applying field settings

Although the field does not support HTML, you can influence the way the value is displayed. The following modifiers are
available for a text field, on top of the [default modifiers](10-using-fields.md#applying-field-settings).

- `->break()` Makes the content break on new lines. It does so through CSS instead of adding `<br/>` tags.
- `->inline()` Displays the content without any breaks (inverse of `break()`).
- `->italic()` Displays the content *as italic*.
- `->roman()` Displays the content as roman (default upright text).
- `->weight( string $weight = '' )` Displays the content according to the provided weight; e.g. `bold` or `500`.

Let's look at a full example:

```php
TextField::create( 'field', 'Label' )
    ->weight('bold') // Adds a `font-weight:bold` style.
    ->italic() // Adds a `text-style:italic` style.
    ->break(); // Adds a `white-spice:pre-line` style.
```

:::note

`->italic()` also has an optional `bool $is_italic` parameter. So instead of `->roman()` you can also
use `->italic( false )`, if that feels more intuitive to you.

:::
