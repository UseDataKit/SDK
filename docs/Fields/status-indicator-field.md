# StatusIndicatorField

The `StatusIndicatorField` is a field which shows a badge like element, which represents a state, for example: Active or
Inactive. The StatusIndicator contains the following state options:

- `active`: A green status indicator with the value `Active`.
- `inactive`: A grey status indicator with the value `Inactive`.
- `info`: A blue status indicator with the value `Info`.
- `warning`: An orange status indicator with the value `Warning`.
- `error`: A red status indicator with the value `Error`.

## Boolean type (true: Active or false: Inactive)

By default, the field has a boolean behavior. The value will be interpreted truthy or falsy. For `true` values it will
show an `Active` status, and for `false` values it will show the `Inactive` status. However, the states used can be
overwritten by calling the `->boolean(string $true, string $false)` modifier method with the desired states.

```php
use DataKit\DataViews\Field\StatusIndicatorField;

// Use an warning state for falsy values.
$status = StatusIndicatorField::create( 'status', 'Status' )
    ->boolean( StatusIndicatorField::STATUS_ACTIVE, StatusIndicatorField::STATUS_WARNING );
```

## Mapping type (every values maps to a status type)

An alternative option is to show a specific state for a specific value. To set this up, you can call the
`->mapping( string $active, ?string $inactive = null, ...)` modifier method. In the next example any `active` value will
be displayed as an `Active` state, and the value of `concept` will be displayed as a `Info` state. All other values
will automatically default to the `Inactive` state.

```php
use DataKit\DataViews\Field\StatusIndicatorField;

$status = StatusIndicatorField::create( 'status', 'Status' )
    ->mapping( 'active', null, null, 'concept' )
    ->show_value(); // Show the value as the text.
```

## Applying field settings

- `->boolean( string $true, string $false )` Makes the field a boolean field with specific state types.
- `->mapping( string $active, ?string $inactive, ... )` Makes the field a mapping field with a state per value.
- `->show_value()` Makes the field display the value as the text.
- `->show_label()` Makes the field display the status label as the text.
