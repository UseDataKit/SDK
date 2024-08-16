# EnumField

An `EnumField` is a field type that contains a fixed set of values (an enumeration). For every value the field provides
a companion label. This label is what is shown on the field.

Because the field requires a set of values, the `EnumField::create()` method also requires an additional `$elements`
parameter. This parameter should receive a key => value array where the key is the value, and the value is the label.

```php
use DataKit\DataViews\Field\EnumField;

EnumField::create( 'status', 'Status', [
    'active' => 'Active',
    'disabled' => 'Disabled',
]);
```

In this example, the field will show the label `Active` on the view, for any dataset that contains the value `active` on
the `status` key.

## Applying field settings

The `EnumField` has a few other settings modifiers on top of
the [default modifiers](10-using-fields.md#applying-field-settings).

- `->filterable_by( Operator ... $operators )` Makes the field filterable. [Read more about operators](#operators)
- `->primary()` Makes the field filterable as a primary filter, which is always
  visible. [Read more about filtering](#filtering)
- `->secondary()` (default) Makes the field filterable as a secondary filter, which is only shown on the "Add Filter"
  component.

## Filtering

The `EnumField` is a special field that can be filtered. These filters are applied by the datasource to filter the
results based on the selected options. There is a limited set of [`Operators`](#operators) available.

Once an EnumField is filterable, it can be either a `primary` or a `secondary` filter. All primary filters are always
visible on the DataView UI, while secondary filters are hidden behind a "add filter" component.

For all filtering options, the `elements` are used as the values to be filtered on.

### Operators

An `EnumField` can be filtered with a limited set of operators.

There are two operators that are used for a single filtering value (you can select one value).

- `Operator::is()` The field value is EQUAL TO the selected value.
- `Operator::isNot()` The field value is NOT EQUAL TO the selected value.

For multiple filter values (you can select multiple values), there are four operators available:

- `Operator::isAny()` The value is at least one of the selected values (e.g. value1 `OR` value2)
- `Operator::isNone()` The value is NOT present in the selected values (e.g. is `NOT` value1 `AND NOT` value2)
- `Operator::isAll()` The field contains ALL the selected options (e.g. has value1 `AND` value2)
- `Operator::isNotAll()` The field does NOT contain ALL the selected options (it can have some or none, but not all)

```php
use DataKit\DataViews\DataView\Operator;

$status = EnumField::create( 'status', 'Status', [
    'active' => 'Active',
    'disabled' => 'Disabled',
])->filterable_by( Operator::isAny(), Operator::isNone() );
```
