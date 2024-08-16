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

## Learn More

`DataViews` are very powerful out-of-the box. They feature:

- [A variety of view types](https://docs.datakit.org/creating-dataviews)
- [Different field types](https://docs.datakit.org/Fields/using-fields)
- [Filtering](https://docs.datakit.org/filters)
- [Sorting](https://docs.datakit.org/Data-sources/create-a-data-source#filtering--sorting-results)
- Searching
- (fast) Pagination
- (bulk) Actions (with View & Delete built-in)

DataKit is also built to be extended by you with ease. You can [create your own `DataSource`](https://github.com/UseDataKit/SDK/blob/main/docs/Data-sources/10-create-a-data-source.md) or [invent your own `Field`](https://github.com/UseDataKit/SDK/blob/main/docs/Fields/10-using-fields.md).

**Please take a look at our [Documentation](docs) to learn more.**

## Be Part of the Community

As a developer we'd love to hear from you. If you have ideas for features, or you found a bug, or just want to show off
what you've create with DataKit; [let us know](https://github.com/UseDataKit/SDK/discussions)!
