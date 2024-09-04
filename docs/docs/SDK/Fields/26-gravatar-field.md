# GravatarField

The `GravatarField` renders a [Gravatar](https://gravatar.com) image for an email address as an `<img />` tag .

## Applying field settings

Under the hood, a `GravatarField` renders like an `ImageField`. This means it has all
the [same image modifiers](25-image-field.md#applying-field-settings) as an `ImageField`.

In addition to those modifiers, the `GravatarField` also provides some modifiers of its own:

- `->default_image( string $default )` Sets the [default](https://docs.gravatar.com/api/avatars/images/#default-image)
  image type for a missing avatar picture.
- `->rating( string $rating )` Sets the [allowed rating](https://docs.gravatar.com/api/avatars/images/#rating) for the
  avatar picture.
- `->resolution( int $size )` Sets the [resolution](https://docs.gravatar.com/api/avatars/images/#size) of the image (
  default: 80).

A full example of this field:

```php
use DataKit\DataViews\Field\GravatarField;

GravatarField::create( 'email', 'Picture' )
    ->resolution( 200 ) // Creates an image that is 200x200
    ->default_image( 'retro' ) // Sets the images default to `retro` for a missing Gravatar picture.
    ->rating( 'g' ) // Sets the rating to `g` for the Gravatar (default value).
    ->size( 100 ) // Adds a `width="100"` attribute to the image tag
    ->alt( 'Profile picture for {name}' );
```

In this example you can notice that we also call the `size()` and `alt()` modifiers from an `ImageField`.

:::info

The `resolution` and `size` are not the same thing. The `resolution` is the size of the image that is used; while the
`size` sets the `width` (and `height`) of the actual `<img />` tag that is being rendered.

:::
