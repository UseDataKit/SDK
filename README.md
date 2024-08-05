# DataKit SDK

DataKit is a PHP-based abstraction
around [`@wordpress/dataviews`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/).
It provides an easy-to-understand way of composing `DataViews`-based applications, with a set of default field types and
rendering.

## Folder Structure

```
DataKit/
├── assets - Contains all the compiled JavaScript & CSS
├── docs - Contains documentation for various parts of the plugin
├── frontend - Contains all the compilables for JavaScript & CSS
├── src - Contains all the PHP code and wrappers
├── templates - Contains any default templates
└── tests - Contains the unit tests for the PHP classes
```

## Getting Started

To install this plugin, follow these instructions:

1. Clone the repository. You can do this directly into your WordPress's `wp-content/plugins`
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

4. Go to your WordPress installation, and activate the DataKit plugin.

## Creating a DataView

DataKit provides a fluent PHP API for creating `DataView` objects. A `DataView` consists of a `DataSource` and a set of
`Fields`. To learn more about the different field types, please see our [Documentation](docs).

1. First create a `DataSource` of your preferred type, for example a `GravityFormsDataSource`.
   ```php
    use DataKit\Plugin\Data\GravityFormsDataSource;
   
    $datasource = new GravityFormsDataSource( 10 ); // A Gravity Forms data source for form ID 10. 
    ```
2. Next you create a `DataView` instance. Currently, we only support the table view.
    ```php
    use DataKit\DataViews\DataView\DataView;
    use DataKit\DataViews\Field\ImageField;
    use DataKit\DataViews\Field\HtmlField;
    use DataKit\DataViews\Field\TextField;

    $dataview = DataView::table(
        'my-dataview', // This is a unique ID we need to reference and differentiate the DataView.
        $datasource, // The data source we just created.
        [ // Add an array of fields to show on the DataView.
            TextField::create( '1', 'Name' )->sortable()->always_visible(),
            ImageField::create( '2', 'Profile Image' )->not_sortable()->alt( 'Profile picture' ),
            HtmlField::create( '3', 'About' )->not_sortable(),
        ]
    );
    ```
3. Register your `DataView` with the repository.
    ```php
    do_action( 'datakit/dataview/register', $dataview );
    ```
4. Show off your `DataView`! You can use the `[dataview id="my-dataview"]` shortcode to display your `DataView` anywhere.

## Learn More

`DataViews` are very powerful out-of-the box. They feature:

- [Filtering](docs/Fields/20-enum-field.md#filtering)
- Searching
- Sorting
- (fast) Pagination
- (bulk) Actions (with View & Delete built-in)
- Different Field types

DataKit is also built to be extended by you with ease. You can [create your own `DataSource`](https://github.com/GravityKit/DataKit/blob/main/docs/Data-sources/10-create-a-data-source.md) or [invent your own `Field`](https://github.com/GravityKit/DataKit/blob/main/docs/Fields/10-using-fields.md).

**Please take a look at our [Documentation](docs) to learn more.**

## Be Part of the Community

As a developer we'd love to hear from you. If you have ideas for features, or you found a bug, or just want to show off
what you've create with DataKit; [let us know](https://github.com/GravityKit/DataKit/discussions)!
