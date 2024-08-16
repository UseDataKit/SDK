---
title: ArrayDataSource
sidebar: auto
---

# ArrayDataSource

The `ArrayDataSource` is an very simple in-memory data source that is highly useful for rapid development and testing
purposes. It can also be used for composition when [creating a new data source](10-create-a-data-source.md).

## Example usage

```php
use DataKit\DataViews\Data\ArrayDataSource;

$array_datasource = new ArrayDataSource( 
    'unique-id',
    [
        'uuid-1' => [ 'name' => 'Zack Katz', 'email' => 'zack@datakit.org' ],
        'uuid-2' => [ 'name' => 'Doeke Norg', 'email' => 'doeke@datakit.org' ],
    ]
);
```

In this example we created a very simple data source with two users. The keys need to be unique strings to differentiate
the various records. The data source is filterable and searchable just like any other date source.

U can use the data source as a stand-in data source for development purposes, while the real data source is still being
developed.

Another use case is to use the `ArrayDataSource`
when [composing a new data source](10-create-a-data-source.md#composing-a-data-source). 
