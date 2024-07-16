# DataKit

DataKit is a PHP-based abstraction
around [`@wordpress/dataviews`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/).
It provides an easy-to-understand way of composing dataviews based applications, with a set of default field types and
rendering.

## Folder structure

```
DataKit/
├── assets - Contains all the compiled javascript & css
├── docs - Contains documentation on various parts of the package
├── frontend - Contains all the compilables for javascript & css
├── src - Contains all the PHP code and wrappers
└── tests - Contains the unit tests for the PHP classes
```

## Getting Started

To install this package/plugin into your WordPress installation, you can follow these instructions:

1. Clone the repository to your local environment. You can do this directly into your WordPress' `wp-content/plugins`
   folder, or somewhere else and symlink the folder.

    ```bash
    git clone git@github.com:GravityKit/DataKit.git DataKit
    ```

2. Symlink your repository to your WordPress' `wp-content/plugins` folder (Not required if you cloned it there directly)

   ```bash
   cd </Your/WP-site/>wp-contents/plugins 
   ln -s <Location-Of-DataKit> DataKit
   ```

3. Go into the folder and perform a Composer install
   ```bash
   composer install --no-dev -o
   ```
4. Go to your WordPress installation, and activate the DataKit Plugin.

## Creating a DataView

DataKit provides a fluent PHP API for creating `DataView` objects. A `DataView` consists of a `DataSource` and a set of
`Fields`. To learn more about the different field types, please see our [Documentation](docs).

1. First create a datasource of your preferred type, for example a `GravityFormsDataSource`.
   ```php
    use DataKit\DataViews\Data\GravityFormsDataSource;
   
    $datasource = new GravityFormsDataSource( 10 ); // A Gravity Forms Data source for Form ID 10 
    ```
2. Next you create a `DataView` instance. Currently, we only support the table view.
    ```php
    use DataKit\DataViews\DataView\DataView;
    use DataKit\DataViews\Field\ImageField;
    use DataKit\DataViews\Field\HtmlField;
    use DataKit\DataViews\Field\TextField;

    $dataview = DataView::table(
        'my-dataview', // This is a unique ID we need to reference and differentiate the dataview.
        $datasource, // The datasource we just created
        [ // Add an array of fields to show on the DataView
            TextField::create( '1', 'Name' )->sortable()->always_visible(),
            ImageField::create( '2', 'Profile Image' )->not_sortable()->alt( 'Profile picture' ),
            HtmlField::create( '3', 'About' )->not_sortable(),
        ]
    );
    ```
3. Register your DataView with the repository
    ```php
    do_action( 'datakit/dataview/register', $dataview );
    ```
4. Show off your DataView! You can use the `[dataview id="my-dataview"]` shortcode to display your DataView anywhere.

## Learn More

DataViews are very powerful out-of-the box. It features:

- [Filtering](docs/Fields/using-fields.md)
- Searching
- Sorting
- (fast) Pagination
- (bulk) Actions (with View & Delete built-in)
- Different Field types

DataKit is also built to be extended by you with ease. You can create your own `DataSource` or invent your own `Field`.
Please take a look at our [Documentation](docs) to learn more about these concepts.

## Be part of the Community

As a developer we'd love to hear from you. If you have ideas for features, or you found a bug, or just want to show off
what you've create with DataKit; [let us know](https://github.com/GravityKit/DataKit/discussions)!
