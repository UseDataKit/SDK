# DataView

DataView is an PHP based abstraction around [`@wordpress/dataviews`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/). It provides an easy-to-understand way of composing
dataview based applications, with a set of default field types and rendering.

## Folder structure

```
DataView/
├── assets - Contains all the compiled javascript
├── frontend - Contains all the compilables for javascript
├── src - Contains all the PHP code and wrappers/
│   ├── Data - Contains anything to do with retrieving data
│   └── Field - Contains anything to do with registering fields
└── tests - Contains the unit tests for the PHP classes
```
[Current Tree structure]

## Fields

Fields are objects that contains a specific set of properties. There are no real "types", but you can influence the way
they are rendered. This means we can create a preset of field renderings. To make this work for anything, we need to
make sure, every field has a fixed data structure.

The field renderer should be a function that we can reference from a standard field library, eg. `fields.html` to
reference a function that renders the value as HTML.

### Filtering

Fields might be filtered. 


[Current Tree structure]: <https://tree.nathanfriend.io/?s=(%27optiEs!(%27fancy!true~fullPaB!false~trailingSlash!true~rootDot!false)~A(%27A%27DaGView.assets6ed3frEtend6ableJ3srcK7od9andIrappers.4DaG0triev5daG.4Field0gister5fields.tests*B9unit%20testJ7lasses.%27)~versiE!%271%27)*%20-%20CEGin8.%5Cn40*anyB5to%20doIiB%20re2%20B93%20javascript.4%20%205ing%206K2compil72PHP%20c8s%209e%20Asource!BthEonGtaI%20wJ8forK*all%01KJIGEBA987654320.*>
