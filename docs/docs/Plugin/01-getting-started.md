---
title: Getting Started
sidebar: auto
---

## Getting started with the DataKit WordPress plugin

<div class="responsive-iframe-container">
    <iframe src="https://www.youtube-nocookie.com/embed/BoCtYkv7QY8?si=CgJPFewh4zMoJ1x1" title="Introduction to DataKit" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
</div>

### Installing from source

To install the WordPress plugin, follow these instructions:

1. Clone [the repository](https://github.com/UseDataKit/DataKit). You can do this directly into your WordPress's
   `wp-content/plugins`
   folder, or somewhere else and symlink the folder.

    ```bash
    git clone git@github.com:UseDataKit/DataKit.git DataKit
    ```

2. Symlink your repository to your WordPress' `wp-content/plugins` folder (Not required if you cloned it there directly)

   ```bash
   cd </Your/WP-site/>wp-contents/plugins 
   ln -s <Location-Of-DataKit> DataKit
   ```

3. Go into the folder and perform a Composer install
   ```bash
   composer install --no-dev
   ```

4. Go to your WordPress installation, and activate the DataKit plugin.

## Creating a DataView

In order to register the DataView, you need to wait until the `datakit/loaded` action hook was dispatched. After this,
you can be certain DataKit was loaded and the default data sources and fields are available to use.

```php
add_action( 'datakit/loaded', function () {
    // Create your DataView here.
} );
```

DataKit provides a fluent PHP API for creating `DataView` objects. A `DataView` consists of a `DataSource` and a set of
`Fields`. To learn more about the different field types, please see our [Documentation](SDK/Fields/using-fields).

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
4. Show off your `DataView`! You can use the `[dataview id="my-dataview"]` shortcode to display your `DataView`
   anywhere.
