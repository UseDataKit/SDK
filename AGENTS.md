# DataKit SDK — Developer & Agent Guide

> **Last Updated:** 2026-03-05
> **Package:** `datakit/sdk`
> **Purpose:** PHP abstraction layer for building data table/grid/list UIs (`@wordpress/dataviews`) plus a unified query engine for SQL-backed data sources

---

## Quick Start

- **Language:** PHP 8.1+ backend, React 18 frontend (Vite build)
- **Namespace:** `DataKit\DataViews\` — PSR-4 autoloaded from `src/`
- **Entry point:** No bootstrap file — this is a library consumed by plugins (e.g., DataKit plugin)
- **Frontend entry:** `frontend/src/main.js` renders `<DataViews>` into `[data-dataview]` DOM elements
- **Build:** `cd frontend && npm run build` outputs to `assets/js/dataview.js` + `assets/css/dataview.css`
- **Tests:** `./vendor/bin/phpunit` — 424 tests, PHPUnit 10.5
- **Core patterns:** Immutable value objects, fluent clone-and-return builders, `__RAW__` JS interop
- **Two layers:** DataView (UI composition) and Query (data retrieval engine)

---

## Repository Structure

```
datakit-sdk/
├── src/
│   ├── DataView/                 # DataView, View, Filter, Sort, Action, Operator
│   ├── Data/                     # DataSource interface + ArrayDataSource, CsvDataSource, etc.
│   │   ├── Exception/            # DataNotFoundException, ActionForbiddenException
│   │   └── DataMatcher/          # ArrayDataMatcher for in-memory search
│   ├── Field/                    # Field types (Text, Html, Link, Image, Enum, DateTime, etc.)
│   ├── Cache/                    # CacheProvider interface + ArrayCacheProvider
│   ├── Clock/                    # Clock interface + SystemClock
│   ├── Query/                    # ← NEW: Unified query engine
│   │   ├── Engine/               # QueryEngine pipeline, QueryBackend interface, Result
│   │   ├── Backend/              # ArrayBackend (reference impl)
│   │   │   └── WordPress/        # SQL backends: GravityForms, WPQuery, WooCommerce,
│   │   │                         #   WordPressUsers, WSForm, EDD + shared base
│   │   ├── Adapter/              # Bridges between DataSource ↔ QueryBackend
│   │   └── Exception/            # QueryValidation, Execution, FieldNotFound, etc.
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
| Operator enum (legacy) | `src/DataView/Operator.php` |
| EnumObject base | `src/DataView/EnumObject.php` |
| Action builder | `src/DataView/Action.php` |
| **QueryBackend interface** | `src/Query/Engine/QueryBackend.php` |
| **QueryEngine pipeline** | `src/Query/Engine/QueryEngine.php` |
| **Query value object** | `src/Query/Query.php` |
| **BackendSchema** | `src/Query/Engine/BackendSchema.php` |
| **Result** | `src/Query/Engine/Result.php` |
| **AbstractWpdbBackend** | `src/Query/Backend/WordPress/AbstractWpdbBackend.php` |
| React DataView component | `frontend/src/DataView/DataView.jsx` |
| JS bootstrap | `frontend/src/main.js` |

---

## Architecture

The SDK has two independent layers:

### Layer 1: DataView (UI Composition)

Composes `@wordpress/dataviews` configurations from PHP. Unchanged from the original design.

```
PHP DataView::to_js()  →  JSON blob with __RAW__ markers  →  <script> tag
                                                                   ↓
frontend/src/main.js  →  reads window.datakit_dataviews  →  renders <DataViews>
```

`__RAW__...__ENDRAW__` markers wrap JavaScript expressions that must be emitted as code. `DataView::to_js()` strips the markers via regex (`src/DataView/DataView.php`).

### Layer 2: Query Engine (Data Retrieval)

A backend-agnostic query pipeline that compiles structured `Query` objects into backend-specific operations (SQL, array filters, etc.) and returns typed `Result` objects.

#### Core Interfaces

```
QueryBackend (interface)           BackendRegistry
├── sourceType(): string           ├── register(QueryBackend)
├── capabilities(): Capability[]   ├── get(sourceType): ?QueryBackend
├── describe(scope): BackendSchema └── sourceTypes(): string[]
├── schemaVersion(): int
├── estimate(Query): ?CostEstimate
├── compile(Query, Schema): CompiledQuery
├── execute(CompiledQuery): Result
└── static isAvailable(): bool
```

#### QueryEngine Pipeline (15 stages)

```
1. Resolve backend (via BackendRegistry + Query.source.type)
2. Validate query structure
3. Get schema + validate field references
4. Clamp limit to maxLimit
5. Cache check (CacheProvider)
6. Cost estimation (CostEstimate)
7. beforeCompile hook (QueryLifecycle)
8. Compile (QueryBackend::compile)
9. afterCompile hook
10. beforeExecute hook
11. Execute (QueryBackend::execute)
12. afterExecute hook
13. Hydrate (ResultHydrator, optional)
14. Add warnings
15. Cache store
```

Extensibility via `QueryLifecycle` interface: `beforeCompile`, `afterCompile`, `beforeExecute`, `afterExecute`, `onError`. `NullLifecycle` provides no-ops.

#### Value Objects (all immutable)

| Class | Purpose | Key methods |
|---|---|---|
| `Query` | Full query specification | `withWhere()`, `withLimit()`, `withOrderBy()`, `withTime()`, `validate()`, `toArray()`, `fromArray()`, `hash()` |
| `Source` | Identifies data backend | `toArray()`, `fromArray()` — plain VO, no backend knowledge |
| `Condition` | Single field comparison | `field`, `operator` (ComparisonOperator), `value` |
| `ConditionGroup` | AND/OR group of conditions | `::and(...)`, `::or(...)`, max 5 nesting depth |
| `SelectField` | Dimension in aggregate query | `field`, `alias` |
| `AggregateField` | Metric in aggregate query | `function` (AggregateFunction), `field`, `alias`, `outputName()` |
| `OrderBy` | Sort clause | `field`, `direction` (SortDirection) |
| `Limit` | Pagination | `limit`, `offset` |
| `TimeRange` | Time filter | `field`, `from`, `to`, `preset` (TimePreset), `grain` (TimeBucket) |
| `Result` | Query results | `Countable`, `IteratorAggregate`, `summarize(n)` for AI token economy |
| `CostEstimate` | Query cost score | `score` (0.0–1.0+), `isExpensive`, `factors[]`, `suggestions[]` |
| `BackendSchema` | Schema description | `fields[]`, `hasField()`, `getField()`, `findClosestField()` (Levenshtein) |
| `FieldSchema` | Single field schema | `key`, `label`, `type` (ColumnType), `operators[]`, `enumValues`, `sortable`, `filterable`, `aggregatable` |

#### PHP 8.1 Enums

| Enum | Cases |
|---|---|
| `QueryType` | `Browse`, `Aggregate` |
| `ColumnType` | `String`, `Integer`, `Float`, `Boolean`, `Datetime` |
| `ComparisonOperator` | `Eq`, `Neq`, `Gt`, `Gte`, `Lt`, `Lte`, `In`, `NotIn`, `Between`, `Contains`, `NotContains`, `StartsWith`, `IsEmpty`, `IsNotEmpty` |
| `AggregateFunction` | `Count`, `CountDistinct`, `Sum`, `Avg`, `Min`, `Max` |
| `Capability` | 27 cases: Filter* (1:1 with ComparisonOperator), Agg*, GroupBy, Having, OrConditions, TimeBucket, Search, OrderBy, LimitOffset, Unnest |
| `SortDirection` | `Asc`, `Desc` |
| `LogicOperator` | `And`, `Or` |
| `TimeBucket` | `Hour`, `Day`, `Week`, `Month`, `Quarter`, `Year` |

#### Backends

| Backend | Source type | Entity | Location |
|---|---|---|---|
| `ArrayBackend` | `array` | any | `src/Query/Backend/ArrayBackend.php` |
| `GravityFormsBackend` | `gravity_forms` | entries | `src/Query/Backend/WordPress/GravityForms/` |
| `WPQueryBackend` | `wp_query` | posts/pages/CPTs | `src/Query/Backend/WordPress/WPQuery/` |
| `WooCommerceBackend` | `woocommerce` | orders, products, customers | `src/Query/Backend/WordPress/WooCommerce/` |
| `WordPressUsersBackend` | `wordpress_users` | users | `src/Query/Backend/WordPress/WordPressUsers/` |
| `WSFormBackend` | `ws_form` | submissions | `src/Query/Backend/WordPress/WSForm/` |
| `EDDBackend` | `edd` | orders, downloads, customers | `src/Query/Backend/WordPress/EDD/` |

All WordPress backends extend `AbstractWpdbBackend` which provides:
- Shared SQL compilation (WHERE, ORDER BY, LIMIT, GROUP BY, HAVING)
- `SqlFilterCompiler` for condition→SQL translation
- `SqlAggregateCompiler` for aggregate function→SQL
- `SqlTimeBucketCompiler` for time bucketing (DATE_FORMAT)
- `WpdbCompiledQuery` as the compiled representation
- `WpdbExecutor` for `$wpdb->get_results()` execution with `%%` unescaping

Each backend implements:
- `buildColumnMap()` — field key → SQL expression
- `getFrom()` — primary table
- `getJoins()` — accumulated JOIN clauses
- `compileScopeWhere()` — entity-specific WHERE
- `estimateRowCount()` — for cost estimation
- `getResultSchema()` — column type inference from compiled query
- `static source()` — factory method for creating `Source` objects

#### Adapter Layer (DataSource ↔ QueryBackend bridge)

| Class | Direction | Purpose |
|---|---|---|
| `LegacyDataSourceAdapter` | DataSource → QueryBackend | Wraps existing `DataSource` to work with `QueryEngine` (browse only) |
| `QueryableDataSource` | QueryBackend → DataSource | Abstract base that extends `BaseDataSource` so `QueryEngine` results plug into the DataView pipeline |

### DataView Layer Concepts

#### DataView (`src/DataView/DataView.php`)

```php
$view = DataView::table( 'my-view', $data_source, [ $field1, $field2 ] )
    ->paginate( 25 )
    ->search( '' )
    ->sort( Sort::asc( 'name' ) )
    ->primary_field( $name_field )
    ->deletable()
    ->viewable( [ $detail_field1, $detail_field2 ] );

$json = $view->to_js(); // JSON for the frontend
```

Named constructors: `DataView::table()`, `DataView::grid()`, `DataView::list()`.

#### Field (`src/Field/Field.php`)

Abstract base. Each field has an ID, label, `render()` returning a `__RAW__` JS function, and visibility/sorting/filtering config. Built-in types: `TextField`, `HtmlField`, `LinkField`, `ImageField`, `GravatarField`, `EnumField`, `DateTimeField`, `StatusIndicatorField`.

`FilterableField` extends `Field` with operators and primary/secondary filter status.

#### EnumObject (`src/DataView/EnumObject.php`)

PHP pseudo-enum pattern for pre-8.1 code (`Operator`, `View`). Uses `__callStatic` with literal key lookup in `cases()`.

**Critical:** Curly/smart quotes in `cases()` keys silently break construction.

---

## Conventions

### Immutability

All modifier methods clone `$this` and return the clone (Field, DataSource layers). Query layer uses `readonly` constructor promotion on properties and `with*()` builder methods that return new instances.

```php
// DataView/Field pattern — clone-and-modify
public function sortable(): self {
    $clone = clone $this;
    $clone->is_sortable = true;
    return $clone;
}

// Query layer pattern — readonly properties, new instance
public function withLimit(Limit $limit): self {
    return new self( /* ... all params with new limit ... */ );
}
```

**Exception:** `DataView` itself mutates `$this` in builder methods (`paginate()`, `search()`, `primary_field()`, etc.) — this is the established pattern, not a bug.

### File & Class Naming

- PSR-4: `DataKit\DataViews\Query\Engine\QueryEngine` → `src/Query/Engine/QueryEngine.php`
- One class per file, class name matches filename
- DataView layer: WordPress coding standards (tabs, Yoda conditions, `snake_case` methods)
- Query layer: PSR-12 style (spaces, `camelCase` methods on new code, but `snake_case` on properties)

### Serialization

All Query value objects implement `toArray()` / `fromArray()` for JSON/MCP serialization. `fromArray()` validates required keys and throws `\InvalidArgumentException` or `QueryValidationException` on invalid input.

### `__RAW__` / `__ENDRAW__` Pattern

When PHP needs to embed JavaScript in JSON output:

```php
return '__RAW__' . $js_expression . '__ENDRAW__';
```

`DataView::to_js()` strips the `"__RAW__` and `__ENDRAW__"` wrappers via regex, leaving raw JS in the output.

### Backend `source()` Factories

Each backend owns its `Source` factory method. Backend-specific knowledge stays with the backend:

```php
GravityFormsBackend::source([1, 2, 3]);              // entries from forms 1,2,3
GravityFormsBackend::sourceForForm(5);                // single form shorthand
WooCommerceBackend::source('orders', ['statuses' => ['wc-completed']]);
WordPressUsersBackend::source(['role' => 'admin']);
EDDBackend::source('customers');
```

`Source` itself is a plain value object with no backend knowledge.

---

## Extension Patterns

### Adding a New SQL Backend

1. Create a directory: `src/Query/Backend/WordPress/YourBackend/`

2. Create a table detector (if the backend uses custom tables):

```php
final class YourTableDetector {
    public function getMainTable(): string {
        global $wpdb;
        return $wpdb->prefix . 'your_table';
    }
}
```

3. Extend `AbstractWpdbBackend`:

```php
final class YourBackend extends AbstractWpdbBackend {
    private const COLUMNS = [
        'id' => 'id',
        'status' => 'status',
        'created_at' => 'date_created',  // semantic alias
    ];

    public static function isAvailable(): bool {
        return class_exists('Your_Plugin_Class');
    }

    public function sourceType(): string { return 'your_backend'; }

    public static function source(string $entity = 'items', array $scope = []): Source {
        return new Source('your_backend', $entity, $scope);
    }

    public function capabilities(): array { return [ /* Capability cases */ ]; }
    public function describe(array $scope): BackendSchema { /* field schemas */ }
    protected function buildColumnMap(Query $query, BackendSchema $schema): array { /* ... */ }
    protected function getFrom(Query $query): string { return 'your_table AS o'; }
    protected function getJoins(): array { return $this->joins; }
    protected function compileScopeWhere(Query $query): array { /* entity filter */ }
    protected function estimateRowCount(Query $query): ?int { /* COUNT(*) */ }
    protected function getResultSchema(WpdbCompiledQuery $compiled): array { /* type map */ }
}
```

4. Implement `collectAllFieldKeys()` — must handle both Browse mode (all schema fields) and Aggregate mode (only referenced fields). Use the same pattern as existing backends.

5. Write tests in `tests/Query/Backend/WordPress/YourBackend/`. Stub `$wpdb` in `setUp()`:

```php
global $wpdb;
$wpdb = new class {
    public string $prefix = 'wp_';
    public function prepare(string $q, ...$a): string { /* ... */ }
    public function esc_like(string $t): string { return addcslashes($t, '_%\\'); }
};
```

6. Register with `BackendRegistry::register(new YourBackend())`.

### Adding a New Field Type (DataView layer)

1. Extend `Field` (or `FilterableField`):

```php
final class MyField extends Field {
    protected function render(): string {
        return sprintf(
            '__RAW__( data ) => datakit_fields.mytype("%s", data, %s)__ENDRAW__',
            $this->uuid(),
            json_encode( $this->context )
        );
    }
}
```

2. Register a JS renderer in `frontend/src/main.js` on `window.datakit_fields`.

### Adding a New Operator (Query layer)

1. Add a case to `ComparisonOperator` enum (`src/Query/ComparisonOperator.php`)
2. Add a matching `Capability::Filter*` case (`src/Query/Engine/Capability.php`)
3. Add the mapping in `Capability::fromComparisonOperator()`
4. Add handling in `SqlFilterCompiler::compileCondition()` for SQL translation
5. If the operator bridges to the legacy `Operator`, add mapping in `ComparisonOperator::toOperator()`

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

Output: `assets/js/dataview.js` (IIFE) + `assets/css/dataview.css`. Both are committed.

### Testing

```bash
./vendor/bin/phpunit                                    # Full suite (424 tests)
./vendor/bin/phpunit tests/Query/Backend/WordPress/EDD/ # Backend tests
./vendor/bin/phpunit --filter=test_browse_query          # Single test
```

PHPUnit 10.5 with PHPUnit 10 XML schema. Data providers use `@dataProvider methodName` (no trailing text — PHPUnit 10 is strict).

Test utilities:
- `ArrayDataSource` — in-memory data source for fixtures
- `ArrayBackend` — reference query backend (supports ALL capabilities)
- `ArrayCacheProvider` — in-memory cache with TTL simulation
- `tests/Clock/FrozenClock.php` — fixed-time clock for deterministic tests
- `tests/Data/TraceableDataSource.php` — records method calls for assertions

### Code Quality

```bash
./vendor/bin/phpstan analyse        # Static analysis
./vendor/bin/phpcs                  # WordPress coding standards
vendor/bin/phplint                  # PHP syntax lint
cd frontend && npm run lint         # ESLint
```

---

## Gotchas & Notes

### 1. Curly Quotes Kill EnumObject

`EnumObject::__callStatic()` does a literal string lookup in `cases()`. Curly/smart quotes (`'` or `'`) in keys silently break construction. Verify with:

```bash
LC_ALL=C grep -P '\xe2\x80[\x98\x99\x9c\x9d]' src/DataView/Operator.php
```

### 2. `readonly` Class vs Property

PHP target is 8.1. `readonly` on **properties** (constructor promotion) is fine. `readonly` on **classes** requires 8.2 — do not use it. The class modifier was removed in `fb93255`.

### 3. Filter::matches() Falsy Values

`Filter::matches()` uses `null === $value` to detect missing fields. Previously `! $value` caused `0`, `"0"`, `""`, and `false` to be treated as missing. Fixed in `aa3ced6`.

### 4. Browse vs Aggregate Field Collection

In `collectAllFieldKeys()`, Browse mode must return **all** schema fields (users expect to see everything). Aggregate mode returns only explicitly referenced fields (dimensions, metrics, time, orderBy, conditions). Every backend implements this pattern — follow it exactly.

### 5. `Source` Is Backend-Agnostic

`Source` is a plain value object with `type`, `entity`, `scope`. It must never contain backend-specific factory methods. Named constructors (`source()`, `sourceForForm()`) belong on the backend class.

### 6. SqlAggregateCompiler Requires Column for Non-COUNT

`SUM()`, `AVG()`, `MIN()`, `MAX()` require a non-null column expression. Only `COUNT()` and `COUNT(DISTINCT)` fall back to `COUNT(*)` when the column is null/empty.

### 7. Committed Build Artifacts

`assets/js/dataview.js` and `assets/css/dataview.css` are committed. After any frontend change, rebuild and commit the assets.

### 8. DataView Mutates In Place

Unlike the immutable Query layer and Field objects, `DataView` builder methods (`paginate()`, `search()`, `primary_field()`, etc.) mutate `$this` and return `$this`. This is intentional and consistent within that class.

### 9. PHPUnit 10 Data Provider Syntax

`@dataProvider methodName` must not have trailing text (e.g., `@dataProvider myProvider The data provider.` fails). PHPUnit 10 parses the annotation strictly.

### 10. No WordPress Dependencies in Core

The SDK core (`src/Query/*.php`, `src/Query/Engine/*.php`, `src/Query/Exception/*.php`) has zero WordPress dependencies. WordPress-specific code lives exclusively in `src/Query/Backend/WordPress/`. Tests for core Query objects must not reference specific backend types — use `'test_backend'` instead.

### 11. WooCommerce Trash Exclusion

WooCommerce order queries must exclude trashed orders in both `count()` and `compileScopeWhere()`. HPOS mode uses `status != 'trash'`, legacy mode uses `post_status != 'trash'`.

### 12. WSForm Column Naming

WSForm's `date_created` column is actually `date_added` in the database (`ws_form_submit` table). The column map corrects this.
