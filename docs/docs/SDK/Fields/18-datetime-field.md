# DateTimeField

The `DateTimeField` renders a datetime value according to a provided format. You can provide the format the value is
read, and the format the value should be displayed in. For both you can also provide the required time zones.

## Applying field settings

The `DateTimeField` has a few other settings modifiers on top of
the [default modifiers](10-using-fields.md#applying-field-settings).

- `->from_format( string $format, ?DateTimeZone $timezone = null)` Makes sure to interpret the value correctly.
- `->to_format( string $format, ?DateTimeZone $timezone = null)` Sets the format to display the datetime in.

Here is a full example:

```php
use DataKit\DataViews\Field\DateTimeField;

// Assume `date_created` has a value of "2024-07-16 15:57:45" 
DateTimeField::create( 'date_created', 'Created on' )
    ->from_format( 'Y-m-d H:i:s', new DateTimeZone( 'UTC' ) ) // The value is stored in UTC.
    ->to_format( 'D, d M Y H:i', new DateTimeZone( 'Europe/Amsterdam' ) ); // Will be displayed as: "Tue, 16 Jul 2024 17:57" (UTC+2). 
```
