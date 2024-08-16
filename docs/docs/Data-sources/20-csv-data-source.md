---
title: CsvDataSource
sidebar: auto
---
# CsvDataSource

DataKit comes with a `CsvDataSource` that reads a CSV or TSV file. You can provide the separator, enclosure and escape
character to suite your specific file.

The datasource assumes the first row to contain all the labels for the columns.

## Example usage

```php
use DataKit\DataViews\Data\CsvDataSource;

$csv_datasource = new CsvDataSource( '<absolute_path_to_csv>' );
$tsv_datasource = new CsvDataSource( '<absolute_path_to_tsv>', "\t" ); // Separate on tabs.
```

## Using WordPress Attachments

The DataKit Plugin also provides a convenient `AttachmentDataSource` that can create datasource's based on attachment
ID's. Instead of creating a `AttachmentDataSource`, it has named constructors for the specific file types.

```php
use DataKit\Plugin\Data\AttachmentDataSource;

// In both these cases, the datasource will be a `CsvDataSource` with the absolute path resolved.
$csv_datasource = AttachmentDataSource::csv( 109 );
$tsv_datasource = AttachmentDataSource::csv( 109, "\t" ); 
```
