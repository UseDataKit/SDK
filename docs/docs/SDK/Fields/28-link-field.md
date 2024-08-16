# LinkField

A `LinkField` will render the content as a `<a>` tag. By default, it will only link the value. But you can change the
label, or use the value as the label; and link to a different field.

## Applying field settings

The `LinkField` has a few other settings modifiers on top of
the [default modifiers](10-using-fields.md#applying-field-settings).

- `->link_to_field( string $field_id )` Will use the current value as a label, and link to the value of another field.
- `->on_new_window()` Will open the link on a new window by adding `target="_blank"` to the `<a>` tag.
- `->on_same_window()` Will open the link in the current window (default).
- `->with_label( string $label )` Will set the label for the link (supports merge tags).
- `->withtout_label()` Will use the value as the label (default).

Let's look a few examples:

```php
use DataKit\DataViews\Field\LinkField;

// In the following comments the {field} merge tags represent the value for that field.
LinkField::create( 'url', 'Simple Url' )
    ->on_new_window(); // <a href="{url}" target="_blank">{url}</a>

LinkField::create( 'name', 'Full name' )
    ->link_to_field( 'website' ); // <a href="{website}">{name}</a>

LinkField::create( 'url', 'Custom Label' )
    ->with_label( 'See URL' ); // <a href="{url}">See Url</a>

LinkField::create( 'url', 'Custom Label with Name' )
    ->with_label( 'See URL for {name}' ); // <a href="{url}">See Url for {name}</a>
```
