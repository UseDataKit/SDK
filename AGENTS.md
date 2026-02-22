# DataKit SDK — Developer & Agent Guide

> **Last Updated:** 2026-02-22
> **Package:** `datakit/sdk`
> **Purpose:** PHP abstraction layer around `@wordpress/dataviews` for building data table/grid/list UIs

---

## Quick Start

- **Language:** PHP 7.4+ backend, React 18 frontend (Vite build)
- **Namespace:** `DataKit\DataViews\` — PSR-4 autoloaded from `src/`
- **Entry point:** No bootstrap file — this is a library consumed by plugins (e.g., DataKit plugin)
- **Frontend entry:** `frontend/src/main.js` renders `<DataViews>` into `[data-dataview]` DOM elements
- **Build:** `cd frontend && npm run build` outputs to `assets/js/dataview.js` + `assets/css/dataview.css`
- **Tests:** `./vendor/bin/phpunit` — 126 tests, PHPUnit 8.5
- **Core pattern:** Immutable objects with fluent clone-and-return API
- **JS interop:** PHP generates JSON with `__RAW__...__ENDRAW__` markers for inline JavaScript

---

## Repository Structure

```
datakit-sdk/
├── src/                          # PHP library code
│   ├── DataView/                 # Core DataView, View, Filter, Sort, Action, Operator
│   ├── Data/                     # DataSource interface + implementations
│   │   ├── Exception/            # DataNotFoundException, ActionForbiddenException, etc.
│   │   └── DataMatcher/          # ArrayDataMatcher for in-memory search
│   ├── Field/                    # Field types (Text, Html, Link, Image, Enum, DateTime, etc.)
│   ├── Cache/                    # CacheProvider interface + ArrayCacheProvider
│   ├── Clock/                    # Clock interface + SystemClock
│   └── DataViewException.php
├── frontend/                     # React/Vite frontend
│   ├── src/
│   │   ├── main.js               # Bootstraps DataView components into DOM
│   │   ├── helpers.js            # get(), replace_tags(), datakit_fetch()
│   │   ├── DataView/             # DataView.jsx, useRequest.js, useRequestCallback.js
│   │   ├── Fields/               # Text.jsx, Html.jsx renderers
│   │   ├── Actions/              # Url.js action handler
│   │   ├── Components/           # Modal.jsx
│   │   └── scss/                 # fields.scss, modal.scss, field/_image.scss, etc.
│   ├── package.json
│   └── vite.config.js
├── tests/                        # PHPUnit tests (mirrors src/ structure)
├── docs/                         # Docusaurus documentation site
├── assets/                       # Compiled JS/CSS (committed)
├── composer.json
├── phpunit.xml.dist
└── README.md
```

### Key Files Reference

| Purpose | File |
|---|---|
| DataView composition | `src/DataView/DataView.php` |
| DataSource interface | `src/Data/DataSource.php` |
| Base data source | `src/Data/BaseDataSource.php` |
| Field base class | `src/Field/Field.php` |
| Filter logic | `src/DataView/Filter.php` |
| Operator enum | `src/DataView/Operator.php` |
| Enum base class | `src/DataView/EnumObject.php` |
| Action (button) builder | `src/DataView/Action.php` |
| React DataView component | `frontend/src/DataView/DataView.jsx` |
| JS bootstrap | `frontend/src/main.js` |
| Vite config | `frontend/vite.config.js` |

---

## Architecture

### How PHP and JS Connect

The SDK generates a JSON configuration object in PHP that the React frontend consumes:

```
PHP DataView::to_js()  →  JSON blob with __RAW__ markers  →  <script> tag
                                                                   ↓
frontend/src/main.js  →  reads window.datakit_dataviews  →  renders <DataViews>
```

`__RAW__...__ENDRAW__` markers wrap JavaScript expressions (callbacks, render functions) that must be emitted as code, not strings. `DataView::to_js()` strips the markers and unescapes the content via regex (`src/DataView/DataView.php:546-548`).

### Initialization Flow (Frontend)

1. `main.js` registers global field renderers on `window.datakit_fields` (Proxy)
2. Registers action handlers on `window.datakit_dataviews_actions`
3. Registers `window.datakit_modal` component
4. Creates a shared `QueryClient` (React Query)
5. Finds all `[data-dataview]` elements in the DOM
6. For each, reads config from `window.datakit_dataviews[id]` and renders `<DataView>`

**Expected globals** (set by the consuming plugin):
- `datakit_dataviews` — `Object<string, DataViewConfig>` keyed by DataView ID
- `datakit_dataviews_rest_endpoint` — REST API base URL
- `datakit_fetch_options` — optional default fetch headers/options

### Core Concepts

#### 1. DataSource (`src/Data/DataSource.php`)

Interface for any data backend. Two-step fetch pattern:

```php
$ids  = $source->get_data_ids( $limit, $offset ); // Step 1: get IDs
$data = $source->get_data_by_id( $id );            // Step 2: get record
```

Implementations decide how to optimize. For example, `GravityFormsDataSource` (in the DataKit plugin) microcaches full entries during `get_data_ids()` so `get_data_by_id()` is a pure array lookup — no N+1.

Modifier methods return clones (immutable):

```php
$filtered = $source->filter_by( $filters )->sort_by( $sort )->search_by( $search );
```

Built-in implementations: `ArrayDataSource`, `CsvDataSource`, `CachedDataSource`, `DataSourceDecorator`.

#### 2. DataView (`src/DataView/DataView.php`)

Composes a complete view from a data source, fields, filters, actions, and pagination:

```php
$view = DataView::table( 'my-view', $data_source, [ $field1, $field2 ] )
    ->paginate( 25 )
    ->search( '' )
    ->sort( Sort::asc( 'name' ) )
    ->deletable()
    ->viewable( [ $detail_field1, $detail_field2 ] );

$json = $view->to_js(); // JSON for the frontend
```

Named constructors: `DataView::table()`, `DataView::grid()`, `DataView::list()`.
Add layout support: `->supports( View::Grid(), View::List() )`.

#### 3. Field (`src/Field/Field.php`)

Abstract base for all field types. Each field has:
- An ID and label
- A `render()` method returning a `__RAW__` JavaScript function string
- Visibility, sorting, and filtering configuration
- Optional value callback for computed values

```php
$field = TextField::create( 'name', 'Full Name' )
    ->sortable()
    ->hidden();
```

Built-in types: `TextField`, `HtmlField`, `LinkField`, `ImageField`, `GravatarField`, `EnumField`, `DateTimeField`, `StatusIndicatorField`.

#### 4. EnumObject (`src/DataView/EnumObject.php`)

PHP pseudo-enum pattern used by `Operator`, `View`, and other type-safe value objects:

```php
$op = Operator::is();           // via __callStatic
$op = Operator::try_from('is'); // safe creation, returns null on failure
(string) $op;                   // 'is'
$op->equals( Operator::is() );  // true
```

**Critical:** The `cases()` array keys are matched by `__callStatic` as literal strings. Curly/smart quotes in keys will silently break construction.

#### 5. Filter & Operator (`src/DataView/Filter.php`, `src/DataView/Operator.php`)

Filters use magic `__callStatic` mirroring operator names:

```php
$filter = Filter::is( 'status', 'active' );
$filter = Filter::isAny( 'tag', [ 'php', 'js' ] );
$filter->matches( $data_row ); // in-memory matching
```

Operators grouped by use case:
- **Single value:** `is`, `isNot`
- **Multi value:** `isAny`, `isAll`, `isNone`, `isNotAll`
- **Date:** `on`, `notOn`, `before`, `after`, `beforeInc`, `afterInc`, `inThePast`, `over`
- **Numeric:** `between`, `lessThan`, `greaterThan`, `lessThanOrEqual`, `greaterThanOrEqual`
- **Text:** `contains`, `notContains`, `startsWith`

### Data Flow (Request Lifecycle)

```
Browser                          Frontend                         PHP (REST API)
───────                          ────────                         ──────────────
[data-dataview] DOM element
        ↓
main.js reads config from
window.datakit_dataviews[id]
        ↓
<DataView> renders with
initial data from PHP
        ↓
User changes view/filter/sort
        ↓
useRequest() builds params ──→ GET /views/{id}?filters=...  ──→ DataView resolves
        ↓                                                        data_source()
useQuery invalidates     ←── JSON { data, paginationInfo } ←── get_data() loop
        ↓
DataViews re-renders
```

Custom events dispatched on the DOM element:
- `datakit/view/change` — before view state updates (detail: `{id, old, new}`)
- `datakit/view/changed` — after view state updates (detail: `{id, view}`)
- `datakit/view/selected` — item selection changes (detail: `{id, items}`)

---

## Conventions

### Immutability

All modifier methods clone `$this` and return the clone. Never mutate in place:

```php
// Correct — returns new instance
public function sortable(): self {
    $clone = clone $this;
    $clone->is_sortable = true;
    return $clone;
}
```

### File & Class Naming

- PSR-4: `DataKit\DataViews\DataView\Filter` → `src/DataView/Filter.php`
- One class per file, class name matches filename
- WordPress coding standards (tabs, Yoda conditions, snake_case for methods)
- Properties: `snake_case` private with no getters (direct access internally)

### Field UUIDs

Fields generate stable UUIDs from their ID chain: `Field::uuid()` at `src/Field/Field.php:177`. The UUID uses `---` as a separator and is used as the key in data arrays and JS field identification. `Field::normalize($uuid)` extracts the raw field ID.

### `__RAW__` / `__ENDRAW__` Pattern

When PHP needs to embed JavaScript in JSON output:

```php
return '__RAW__' . $js_expression . '__ENDRAW__';
```

`DataView::to_js()` strips the `"__RAW__` and `__ENDRAW__"` wrappers via regex, leaving the raw JS in the output. This is how `render` functions and `callback` properties become live JavaScript.

---

## Extension Patterns

### Adding a New DataSource

1. Extend `BaseDataSource` (or implement `DataSource` directly):

```php
final class MyDataSource extends BaseDataSource {
    public function id(): string { return 'my-source'; }
    public function get_data_ids( int $limit = 20, int $offset = 0 ): array { /* ... */ }
    public function get_data_by_id( string $id ): array { /* ... */ }
    public function get_fields(): array { /* ... */ }
    public function count(): int { /* ... */ }
}
```

2. `BaseDataSource` provides `filter_by()`, `sort_by()`, `search_by()` — they store state on the clone. Your `get_data_ids()` should read `$this->filters`, `$this->sort`, `$this->search`.

3. For deletion support, also implement `MutableDataSource`:

```php
final class MyDataSource extends BaseDataSource implements MutableDataSource {
    public function can_delete(): bool { return true; }
    public function delete_data_by_id( string ...$ids ): void { /* ... */ }
}
```

### Adding a New Field Type

1. Extend `Field` (or `FilterableField` for filterable fields):

```php
final class MyField extends Field {
    protected function render(): string {
        $uuid = $this->uuid();
        return sprintf(
            '__RAW__( data ) => datakit_fields.mytype("%s", data, %s)__ENDRAW__',
            $uuid,
            json_encode( $this->context )
        );
    }
}
```

2. Register a corresponding JS renderer in the frontend. Add to `main.js`:

```js
import MyType from '@src/Fields/MyType';
// Add to the datakit_fields proxy target:
window.datakit_fields = new Proxy({ html: Html, text: Text, mytype: MyType }, { ... });
```

3. The renderer receives `({ name, item, context })` and returns a React element.

### Adding a New Action Type

Actions are defined in PHP and executed in JS:

```php
// PHP side — define the action
$action = Action::ajax( 'approve', 'Approve', $url, 'POST', ['id' => '{id}'] )
    ->primary( 'yes' )
    ->bulk()
    ->confirm( 'Are you sure?' );
```

The `{id}` merge tag is replaced client-side with the item's actual ID. Action handlers live in `frontend/src/Actions/Url.js`.

### Adding a New Operator

1. Add the case to `Operator::cases()` in `src/DataView/Operator.php`
2. Add a `@method static self yourOp()` docblock annotation
3. If it belongs to a category, add it to the appropriate helper (`dateCases()`, etc.)
4. Add test coverage in `tests/DataView/OperatorTest.php`
5. If the operator requires in-memory matching, add a `case` in `Filter::matches()`
6. Each consuming DataSource must map the operator to its query language (e.g., `GravityFormsDataSource::map_operator()`)

---

## Development

### Setup

```bash
composer install                    # PHP dependencies
cd frontend && npm install          # JS dependencies
```

### Build

```bash
cd frontend
npm run build                       # Production build → assets/
npm run watch                       # Watch mode
npm run dev                         # Vite dev server
```

Output: `assets/js/dataview.js` (IIFE) + `assets/css/dataview.css`.

### Testing

```bash
./vendor/bin/phpunit                                    # Full suite
./vendor/bin/phpunit tests/DataView/FilterTest.php      # Single file
./vendor/bin/phpunit --filter=test_matches_falsy_values  # Single test
```

Test utilities:
- `ArrayDataSource` — in-memory data source for test fixtures
- `ArrayCacheProvider` — in-memory cache with TTL simulation
- `tests/Clock/FrozenClock.php` — fixed-time clock for deterministic tests
- `tests/Data/TraceableDataSource.php` — records method calls for assertions

### Code Quality

```bash
./vendor/bin/phpstan analyse        # Static analysis
./vendor/bin/phpcs                  # WordPress coding standards
cd frontend && npm run lint         # ESLint
```

---

## Gotchas & Notes

### Curly Quotes Kill EnumObject

`EnumObject::__callStatic()` does a literal string lookup in the `cases()` array. If curly/smart quotes (`'` or `'`) appear in array keys instead of straight apostrophes (`'`), construction silently fails. This has happened during AI-assisted editing. Always verify with:

```bash
LC_ALL=C grep -P '\xe2\x80[\x98\x99\x9c\x9d]' src/DataView/Operator.php
```

### Filter::matches() Falsy Values

`Filter::matches()` uses `null === $value` to detect missing fields. Previously it used `! $value` which caused `0`, `"0"`, `""`, and `false` to be treated as missing. Fixed in `aa3ced6`.

### Committed Build Artifacts

`assets/js/dataview.js` and `assets/css/dataview.css` are committed. After any frontend change, rebuild and commit the assets.

### No WordPress Dependency in SDK

The SDK itself has zero WordPress dependencies (`composer.json` only requires `ext-json`). WordPress-specific data sources (Gravity Forms, WP Query, etc.) live in the **DataKit plugin**, not the SDK. The SDK can theoretically be used outside WordPress.

### DataView JSON Serialization

`DataView::to_js()` uses `json_encode` with `JSON_THROW_ON_ERROR`, then post-processes `__RAW__` markers. If any field callback or action produces invalid JSON, it throws `DataViewException`.

### Field::get_value() Callback Chain

When a field has a `callback`, `get_value()` at `src/Field/Field.php` applies the callback to the raw value from the data source. The callback receives `(value, data_row)`. If no callback is set, the raw value for the field's ID is returned. If the value is empty, `default_value` is used.

### Pagination Defaults

`Pagination::default()` returns page 1, 25 per page. The static `Pagination::default_results_per_page()` method changes this globally — useful for the consuming plugin to set a site-wide default, but be aware it's mutable static state.

### Search Parsing

`Search::from_string()` supports `+required -ignored optional` syntax. With parsing enabled (default), `+react -angular vue` means: must contain "react", must NOT contain "angular", may contain "vue". `ArrayDataMatcher` implements this logic for in-memory sources.

### DataSourceDecorator Lazy Init

`DataSourceDecorator` supports lazy instantiation — override `inner_data_source()` to construct the wrapped source on first access. This is useful when construction is expensive (e.g., requires a DB call).

### Grid Layout: mediaField vs badgeFields

As of `@wordpress/dataviews` v4.10+, `mediaField` is a view-level property (not layout-level), and `columnFields` was removed entirely. `badgeFields` remains in the layout. See `DataView::view()` and `DataView::layout()`.
