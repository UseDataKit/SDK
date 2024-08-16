# HtmlField

The `HtmlField` is a really powerful field, as it renders any content given as pure HTML. Especially in combination
with a custom [`callback()`](10-using-fields.md#change-value-before-rendering) method; there is little this field
cannot do.

## Security

Allowing HTML on a field can be a security issue. Especially when the data you are providing is coming from users or
a third-party service. To shield you from certain of these issues; the `HtmlField` will strip away all `<script>` tags
from the content, before adding it to the page.

## Applying field settings

While an `HtmlField` is a real power house, it does come with some custom modifiers, on top of on top of
the [default modifiers](10-using-fields.md#applying-field-settings).

- `->allow_scripts()` Includes all `<script>` tags and executes their content.
- `->deny_scripts()` Removes all `<scripts>` tags from the content (default).

```php
use DataKit\DataViews\Field\HtmlField;

HtmlField::create( 'html', 'Html label' );
```

## Custom field instead of using a callback

While the `callback()` method is really convenient to easily change certain formatting of your value, it can become
cumbersome to add the same callback on multiple fields. Also, adding a callback can prevent fields from being
serialized, which can be a requirement when storing the field configuration between requests (for example in a
database).

In these cases it might make more sense to create a custom field. While you cannot `extend` the `HtmlField` as it
is `final`, you can create a new field that defers the rendering to an `HtmlField`. Here is an example of what it
would take to create a (fictive) `MarkdownField`.

```php
use DataKit\DataViews\Field\Field;
use DataKit\DataViews\Field\HtmlField;

final class MarkdownField extends Field {
    // This is the field that will do the actual rendering.
    private HtmlField $html;
    
    // Overwrite the constructor to instantiate the wrapped HtmlField.
    protected function __construct( string $id,string $label ) {
        parent::__construct( $id, $label );

        $this->html = HtmlField::create( $id, $header )->allow_scripts();
    }

    // Overwrite to parse the markdown content and return it as HTML.    
    public function get_value( array $data ) {
        $markdown = parent::get_value( $data );

        // Call a (fictive) MarkdownParser service to generate the HTML.
        return MarkdownParser::parse( $markdown );
    }
    
    // Overwrite render to call the `HtmlField::render()` method.
    public function render(): string {
        return isset( $this->html ) ? $this->html->render() : parent::render();
    }
}
```
