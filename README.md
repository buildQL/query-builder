# BuildQL Query Builder

A secure, fluent, and lightweight SQL Query Builder for PHP 8.0+, inspired by Laravel's Eloquent Query Builder. BuildQL provides an expressive and intuitive API for building complex database queries without writing raw SQL.

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Features

- **Fluent Interface**: Chain methods to build complex queries elegantly
- **Security First**: Built-in protection against SQL injection using prepared statements
- **Laravel-Style Syntax**: Familiar API for developers coming from Laravel
- **Multiple Join Types**: Support for INNER, LEFT, RIGHT, and CROSS joins
- **Aggregate Functions**: Built-in support for COUNT, SUM, MIN, MAX, AVG
- **Advanced Filtering**: WHERE, OR WHERE, WHERE IN, WHERE NULL conditions
- **Query Builder**: GROUP BY, HAVING, ORDER BY, LIMIT, OFFSET support
- **Raw SQL Support**: Execute custom queries when needed
- **Environment Configuration**: Optional .env file support via phpdotenv
- **Comprehensive Testing**: Fully tested with Pest PHP

## Requirements

- PHP >= 8.0
- MySQLi extension
- MySQL/MariaDB database

## Installation

Install via Composer:

```bash
composer require buildql/query-builder
```

## Quick Start

### Basic Configuration

```php
<?php
require 'vendor/autoload.php';

use BuildQL\Database\Query\DB;

// Manual configuration, if you want to manually define your database connection and configuration
// or when you need to change the database credentials only for a specific web page.
DB::setConnection(
    'localhost',  // host
    'root',       // username
    '',           // password
    'my_database',// database (optional)
    'db_port'     // Port (Optional)
);

// Now you can use the query builder
$users = DB::table('users')->get();
```

### Using .env File (Optional)

Create a `.env` file in your project root:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=your_password
DB_DATABASE=your_database
```

Bootstrap the connection:

```php
<?php
use BuildQL\Database\Query\DB;

DB::boot(); // Automatically loads from .env
```

---

## API Reference

### Database Connection Methods

#### `DB::setConnection()`

Manually establish a database connection.

```php
public static function setConnection(
    string $server, 
    string $username, 
    string $pass, 
    ?string $database = null, 
    int $port = 3306
): void
```

**Parameters:**
- `$server`: Database host (e.g., 'localhost', '127.0.0.1')
- `$username`: Database username
- `$pass`: Database password
- `$database`: Database name (optional, can be set later)
- `$port`: MySQL port (default: 3306)

**Example:**
```php
DB::setConnection('localhost', 'root', '', 'my_app');
```

**Note:** Connection can only be established once. Call `DB::resetConnection()` first to reconnect.

---

#### `DB::boot()`

Load database credentials from `.env` file and establish connection.

```php
public static function boot(): void
```

**Requirements:**
- `.env` file must exist in parent directory of vendor folder
- Must contain: `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_PORT`
- Optional: `DB_DATABASE`

**Example:**
```php
DB::boot();
```

**Throws:** `BuilderException` if credentials are missing or invalid.

---

#### `DB::table()`

Create a new query builder instance for a specific table.

```php
public static function table(string $table, ?string $database = null): Builder
```

**Parameters:**
- `$table`: Table name, optionally with alias (e.g., 'users', 'users:u')
- `$database`: Override global database for this query

**Example:**
```php
$query = DB::table('users');
$query = DB::table('users:u'); // With alias
$query = DB::table('users', 'other_db'); // Different database
```

---

#### `DB::raw()`

Execute raw SQL queries with parameter binding.

```php
public static function raw(string $sql, array $bind = []): mixed
```

**Parameters:**
- `$sql`: Raw SQL query with `?` placeholders
- `$bind`: Array of values to bind to placeholders

**Returns:**
- `array`: For SELECT queries (associative array of results)
- `bool`: For INSERT, UPDATE, DELETE queries

**Example:**
```php
// SELECT query
$results = DB::raw("SELECT * FROM users WHERE age > ?", [18]);

// INSERT query
$success = DB::raw("INSERT INTO users (name, email) VALUES (?, ?)", ['John', 'john@example.com']);

// UPDATE query
$success = DB::raw("UPDATE users SET status = ? WHERE id = ?", ['active', 1]);
```

---

#### `DB::setDatabaseGlobally()`

Set the default database for all queries.

```php
public static function setDatabaseGlobally(string $database): void
```

**Example:**
```php
DB::setDatabaseGlobally('my_app');
```

---

#### `DB::resetConnection()`

Close the current database connection.

```php
public static function resetConnection(): void
```

**Example:**
```php
DB::resetConnection();
```

---

### Query Builder Methods

All query builder methods return `$this` for method chaining unless otherwise specified.

---

#### `select()`

Specify columns to retrieve.

```php
public function select(array $columns = ['*']): self
```

**Parameters:**
- `$columns`: Array of column names, with optional aliases

**Column Formats:**
- `'column'` - Simple column
- `'table.column'` - Qualified column
- `'column:alias'` - Column with alias
- `'table.column:alias'` - Qualified column with alias
- `'count(column):total'` - Aggregate with alias

**Examples:**
```php
// Select all columns
DB::table('users')->select(['*'])->get();

// Select specific columns
DB::table('users')->select(['name', 'email'])->get();

// With aliases
DB::table('users')->select(['name:user_name', 'email:user_email'])->get();

// Qualified columns (with table name)
DB::table('users')
    ->join('profiles', 'users.id', 'profiles.user_id')
    ->select(['users.name', 'profiles.bio'])
    ->get();

// Aggregate functions in select
DB::table('orders')
    ->select(['user_id', 'count(*):total_orders', 'sum(amount):total_spent'])
    ->groupBy('user_id')
    ->get();
```

---

#### `selectAggregate()`

Add aggregate functions to the query.

```php
public function selectAggregate(
    ?string $count = null,
    ?string $sum = null,
    ?string $min = null,
    ?string $max = null,
    ?string $avg = null
): self
```

**Parameters:** Each parameter accepts `'column'` or `'column:alias'` format.

**Examples:**
```php
// Count records
DB::table('users')
    ->selectAggregate(count: '*:total_users')
    ->first();
// Result: ['total_users' => 150]

// Multiple aggregates
DB::table('orders')
    ->select(['user_id'])
    ->selectAggregate(
        count: '*:order_count',
        sum: 'amount:total',
        avg: 'amount:average'
    )
    ->groupBy('user_id')
    ->get();
```

---

#### `distinct()`

Return only unique rows.

```php
public function distinct(): self
```

**Example:**
```php
// Get unique cities
DB::table('users')
    ->select(['city'])
    ->distinct()
    ->get();
```

---

#### `where()`

Add a WHERE condition to filter results.

```php
public function where(string $column, $operator, $value = null): self
```

**Parameters:**
- `$column`: Column name
- `$operator`: Comparison operator or value (if `$value` is null)
- `$value`: Value to compare (optional)

**Supported Operators:** `=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE`, `NOT LIKE`

**Examples:**
```php
// Equal comparison (shorthand)
DB::table('users')->where('status', 'active')->get();

// Explicit operator
DB::table('users')->where('age', '>', 18)->get();

// LIKE operator
DB::table('users')->where('name', 'LIKE', '%John%')->get();

// Multiple WHERE conditions (AND)
DB::table('users')
    ->where('status', 'active')
    ->where('age', '>=', 18)
    ->where('country', 'USA')
    ->get();
```

---

#### `orWhere()`

Add an OR WHERE condition.

```php
public function orWhere(string $column, $operator, $value = null): self
```

**Example:**
```php
// WHERE status = 'active' OR status = 'pending'
DB::table('users')
    ->where('status', 'active')
    ->orWhere('status', 'pending')
    ->get();

// WHERE (age < 18) OR (age > 65)
DB::table('users')
    ->where('age', '<', 18)
    ->orWhere('age', '>', 65)
    ->get();
```

**Note:** AND has higher precedence than OR in MySQL. For complex conditions, be aware of operator precedence.

---

#### `whereIn()`

Filter by a set of values.

```php
public function whereIn(string $column, array $values): self
```

**Example:**
```php
// WHERE id IN (1, 2, 3, 4, 5)
DB::table('users')
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->get();

// Multiple whereIn conditions (AND)
DB::table('products')
    ->whereIn('category_id', [1, 2, 3])
    ->whereIn('status', ['active', 'featured'])
    ->get();
```

---

#### `orWhereIn()`

Add an OR WHERE IN condition.

```php
public function orWhereIn(string $column, array $values): self
```

**Example:**
```php
DB::table('users')
    ->whereIn('role', ['admin', 'moderator'])
    ->orWhereIn('permission', ['write', 'delete'])
    ->get();
```

---

#### `whereNotIn()`

Exclude records matching a set of values.

```php
public function whereNotIn(string $column, array $values): self
```

**Example:**
```php
// Exclude specific user IDs
DB::table('users')
    ->whereNotIn('id', [1, 2, 3])
    ->get();

// Exclude blocked statuses
DB::table('accounts')
    ->whereNotIn('status', ['banned', 'suspended', 'deleted'])
    ->get();
```

---

#### `orWhereNotIn()`

Add an OR WHERE NOT IN condition.

```php
public function orWhereNotIn(string $column, array $values): self
```

**Example:**
```php
DB::table('users')
    ->whereNotIn('status', ['banned'])
    ->orWhereNotIn('role', ['guest'])
    ->get();
```

---

#### `whereNull()`

Filter records where a column is NULL.

```php
public function whereNull(string $column): self
```

**Example:**
```php
// Find users without email verification
DB::table('users')
    ->whereNull('email_verified_at')
    ->get();

// Multiple NULL checks
DB::table('profiles')
    ->whereNull('phone')
    ->whereNull('address')
    ->get();
```

---

#### `orWhereNull()`

Add an OR WHERE NULL condition.

```php
public function orWhereNull(string $column): self
```

**Example:**
```php
DB::table('users')
    ->whereNull('deleted_at')
    ->orWhereNull('banned_at')
    ->get();
```

---

#### `whereNotNull()`

Filter records where a column is NOT NULL.

```php
public function whereNotNull(string $column): self
```

**Example:**
```php
// Find verified users
DB::table('users')
    ->whereNotNull('email_verified_at')
    ->get();
```

---

#### `orWhereNotNull()`

Add an OR WHERE NOT NULL condition.

```php
public function orWhereNotNull(string $column): self
```

**Example:**
```php
DB::table('users')
    ->whereNotNull('phone')
    ->orWhereNotNull('mobile')
    ->get();
```

---

#### `join()`

Join tables using INNER JOIN.

```php
public function join(
    string $table, 
    ?string $primaryKey = null, 
    ?string $foreignKey = null, 
    string $type = "inner"
): self
```

**Parameters:**
- `$table`: Table to join (with optional alias using `:`)
- `$primaryKey`: First column in ON clause
- `$foreignKey`: Second column in ON clause
- `$type`: Join type ('inner', 'left', 'right', 'cross')

**Examples:**
```php
// Simple INNER JOIN
DB::table('users')
    ->join('profiles', 'users.id', 'profiles.user_id')
    ->select(['users.name', 'profiles.bio'])
    ->get();

// JOIN with table aliases
DB::table('users:u')
    ->join('profiles:p', 'u.id', 'p.user_id')
    ->join('posts:po', 'u.id', 'po.user_id')
    ->select(['u.name', 'p.bio', 'count(po.id):post_count'])
    ->groupBy('u.id')
    ->get();

// Multiple JOINs
DB::table('orders')
    ->join('users', 'orders.user_id', 'users.id')
    ->join('products', 'orders.product_id', 'products.id')
    ->select(['users.name', 'products.title', 'orders.amount'])
    ->get();
```

---

#### `leftJoin()`

Perform a LEFT JOIN.

```php
public function leftJoin(string $table, string $primaryKey, string $foreignKey): self
```

**Example:**
```php
// Get all users, including those without profiles
DB::table('users')
    ->leftJoin('profiles', 'users.id', 'profiles.user_id')
    ->select(['users.name', 'profiles.bio'])
    ->get();
```

---

#### `rightJoin()`

Perform a RIGHT JOIN.

```php
public function rightJoin(string $table, string $primaryKey, string $foreignKey): self
```

**Example:**
```php
DB::table('orders')
    ->rightJoin('users', 'orders.user_id', 'users.id')
    ->get();
```

---

#### `crossJoin()`

Perform a CROSS JOIN (Cartesian product).

```php
public function crossJoin(string $table): self
```

**Example:**
```php
// Generate all combinations of sizes and colors
DB::table('sizes')
    ->crossJoin('colors')
    ->get();
```

---

#### `groupBy()`

Group results by one or more columns.

```php
public function groupBy(string ...$columns): self
```

**Examples:**
```php
// Group by single column
DB::table('orders')
    ->select(['user_id', 'count(*):total_orders'])
    ->groupBy('user_id')
    ->get();

// Group by multiple columns
DB::table('sales')
    ->select(['country', 'city', 'sum(amount):total'])
    ->groupBy('country', 'city')
    ->get();
```

---

#### `having()`

Filter grouped results (use after `groupBy()`).

```php
public function having(string $column, $operator, $value = null): self
```

**Examples:**
```php
// Users with more than 5 orders
DB::table('orders')
    ->select(['user_id', 'count(*):order_count'])
    ->groupBy('user_id')
    ->having('order_count', '>', 5)
    ->get();

// Multiple HAVING conditions
DB::table('sales')
    ->select(['product_id', 'sum(amount):total', 'count(*):sales_count'])
    ->groupBy('product_id')
    ->having('total', '>', 1000)
    ->having('sales_count', '>=', 10)
    ->get();
```

---

#### `orHaving()`

Add an OR HAVING condition.

```php
public function orHaving(string $column, $operator, $value = null): self
```

**Example:**
```php
DB::table('orders')
    ->select(['user_id', 'count(*):total', 'sum(amount):revenue'])
    ->groupBy('user_id')
    ->having('total', '>', 10)
    ->orHaving('revenue', '>', 5000)
    ->get();
```

---

#### `orderBy()`

Sort results by one or more columns.

```php
public function orderBy(string $column, string $sort = "ASC"): self
```

**Parameters:**
- `$column`: Column to sort by
- `$sort`: Sort direction ('ASC' or 'DESC', default: 'ASC')

**Examples:**
```php
// Sort ascending (default)
DB::table('users')->orderBy('name')->get();

// Sort descending
DB::table('users')->orderBy('created_at', 'DESC')->get();

// Multiple sort columns
DB::table('products')
    ->orderBy('category', 'ASC')
    ->orderBy('price', 'DESC')
    ->get();
```

---

#### `limit()`

Limit the number of results.

```php
public function limit(int $limit, ?int $offset = null): self
```

**Parameters:**
- `$limit`: Maximum number of records to return
- `$offset`: Number of records to skip (optional)

**Examples:**
```php
// Get first 10 records
DB::table('users')->limit(10)->get();

// Skip 20 records, get next 10 (pagination)
DB::table('users')->limit(10, 20)->get();
```

---

#### `offset()`

Skip a number of records.

```php
public function offset(int $offset): self
```

**Example:**
```php
// Page 3 of results (20 per page)
DB::table('posts')
    ->orderBy('created_at', 'DESC')
    ->limit(20)
    ->offset(40)
    ->get();
```

---

### Query Execution Methods

These methods execute the query and return results.

---

#### `get()`

Execute the query and return all matching records.

```php
public function get(array $columns = ["*"]): array
```

**Parameters:**
- `$columns`: Columns to retrieve (optional, overrides `select()`)

**Returns:** Array of associative arrays

**Examples:**
```php
// Get all users
$users = DB::table('users')->get();

// Get specific columns
$users = DB::table('users')->get(['id', 'name', 'email']);

// With WHERE condition
$activeUsers = DB::table('users')
    ->where('status', 'active')
    ->get();

// Result format:
// [
//     ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
//     ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
// ]
```

---

#### `all()`

Get all records from the table (no filtering).

```php
public function all(): array
```

**Example:**
```php
$allUsers = DB::table('users')->all();
```

**Note:** Equivalent to `DB::table('users')->get()` without any conditions.

---

#### `first()`

Get the first matching record.

```php
public function first(array $columns = ['*']): array
```

**Returns:** Associative array (single record) or empty array if no match

**Examples:**
```php
// Get first user
$user = DB::table('users')->first();
// Result: ['id' => 1, 'name' => 'John', ...]

// Get first matching record
$admin = DB::table('users')
    ->where('role', 'admin')
    ->first();

// Specific columns
$user = DB::table('users')->first(['id', 'name']);
```

---

#### `find()`

Find a record by its ID.

```php
public function find(int $id, array $columns = ['*']): array
```

**Parameters:**
- `$id`: Primary key value (assumes column name is 'id')
- `$columns`: Columns to retrieve

**Returns:** Single record or empty array

**Examples:**
```php
// Find user by ID
$user = DB::table('users')->find(1);

// With specific columns
$user = DB::table('users')->find(1, ['name', 'email']);

// Works with table aliases
$user = DB::table('users:u')
    ->join('profiles:p', 'u.id', 'p.user_id')
    ->find(1, ['u.name', 'p.bio']);
```

---

#### `count()`

Count the number of matching records.

```php
public function count(): int
```

**Returns:** Integer count

**Examples:**
```php
// Count all users
$total = DB::table('users')->count();

// Count with condition
$activeUsers = DB::table('users')
    ->where('status', 'active')
    ->count();

// Count distinct values
$uniqueCities = DB::table('users')
    ->select(['city'])
    ->distinct()
    ->count();
```

---

#### `insert()`

Insert a new record into the table.

```php
public function insert(array $data): bool
```

**Parameters:**
- `$data`: Associative array (column => value)

**Returns:** `true` on success

**Examples:**
```php
// Insert single record
$success = DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'created_at' => date('Y-m-d H:i:s')
]);

// Insert with NULL values
DB::table('profiles')->insert([
    'user_id' => 1,
    'bio' => 'Software Developer',
    'phone' => null  // NULL value
]);
```

**Note:** Does not return the inserted ID. Use `DB::raw("SELECT LAST_INSERT_ID()")` if needed.

---

#### `update()`

Update existing records.

```php
public function update(array $data): bool
```

**Parameters:**
- `$data`: Associative array of columns to update

**Returns:** `true` on success

**Important:** Requires at least one WHERE condition

**Examples:**
```php
// Update single record
DB::table('users')
    ->where('id', 1)
    ->update([
        'name' => 'John Smith',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

// Update multiple records
DB::table('users')
    ->where('status', 'pending')
    ->update(['status' => 'active']);

// Update with multiple conditions
DB::table('orders')
    ->where('status', 'pending')
    ->where('created_at', '<', date('Y-m-d', strtotime('-30 days')))
    ->update(['status' => 'cancelled']);
```

---

#### `delete()`

Delete records from the table.

```php
public function delete(): bool
```

**Returns:** `true` on success

**Important:** Requires at least one WHERE condition

**Examples:**
```php
// Delete single record
DB::table('users')
    ->where('id', 1)
    ->delete();

// Delete multiple records
DB::table('logs')
    ->where('created_at', '<', date('Y-m-d', strtotime('-90 days')))
    ->delete();

// Delete with multiple conditions
DB::table('spam')
    ->where('reported', '>', 5)
    ->whereNull('verified_at')
    ->delete();
```

---

#### `insertOrUpdate()`

Insert a new record or update if it exists.

```php
public function insertOrUpdate(array $data, array $where = []): bool
```

**Parameters:**
- `$data`: Data to insert/update
- `$where`: Conditions to check existence (column => value) or (column => [value1, value2])

**Returns:** `true` on success

**Examples:**
```php
// Update if exists, insert if not
DB::table('settings')->insertOrUpdate(
    ['value' => 'dark'],
    ['key' => 'theme']
);

// With multiple conditions
DB::table('user_preferences')->insertOrUpdate(
    [
        'user_id' => 1,
        'preference' => 'notifications',
        'value' => 'enabled'
    ],
    [
        'user_id' => 1,
        'preference' => 'notifications'
    ]
);

// With whereIn condition
DB::table('inventory')->insertOrUpdate(
    ['stock' => 100],
    ['product_id' => [1, 2, 3]]  // Array value = whereIn
);
```

**Note:** Executes a SELECT COUNT query first. For better performance with unique keys, consider using MySQL's `INSERT ... ON DUPLICATE KEY UPDATE`.

---

#### `toRawSql()`

Get the generated SQL query without executing it. (for debugging)

```php
public function toRawSql(): string
```

**Returns:** SQL query string with `?` placeholders

**Examples:**
```php
$sql = DB::table('users')
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->toRawSql();
// Result: "SELECT * FROM `users` WHERE `status` = ? AND `age` > ?"

// Complex query preview
$sql = DB::table('orders:o')
    ->join('users:u', 'o.user_id', 'u.id')
    ->select(['u.name', 'count(o.id):total'])
    ->where('o.status', 'completed')
    ->groupBy('u.id')
    ->having('total', '>', 5)
    ->orderBy('total', 'DESC')
    ->toRawSql();

// Result: SELECT `u`.`name`, count(`o`.`id`) as `total` FROM `orders` as `o` INNER JOIN `users` as `u` ON `o`.`user_id` = `u`.`id` WHERE `o`.`status` = ? GROUP BY `u`.`id` HAVING `total` > ? ORDER BY `total` DESC
```

**Use Case:** Debugging, logging, or query optimization analysis.

---

## Practical Examples

### User Management System

```php
// Register new user
DB::table('users')->insert([
    'name' => 'Alice Johnson',
    'email' => 'alice@example.com',
    'password' => password_hash('secure_password', PASSWORD_DEFAULT),
    'role' => 'user',
    'created_at' => date('Y-m-d H:i:s')
]);

// Authenticate user
$user = DB::table('users')
    ->where('email', 'alice@example.com')
    ->whereNotNull('email_verified_at')
    ->first();

if ($user && password_verify('secure_password', $user['password'])) {
    // Login successful
}

// Get user with profile
$userProfile = DB::table('users:u')
    ->leftJoin('profiles:p', 'u.id', 'p.user_id')
    ->where('u.id', 1)
    ->first([
        'u.id', 'u.name', 'u.email',
        'p.bio', 'p.avatar', 'p.location'
    ]);
```

### E-commerce Product Search

```php
// Search products with filters
$products = DB::table('products')
    ->where('name', 'LIKE', '%laptop%')
    ->where('price', '>=', 500)
    ->where('price', '<=', 2000)
    ->whereIn('category_id', [1, 2, 3])
    ->whereNotNull('stock')
    ->where('status', 'active')
    ->orderBy('price', 'ASC')
    ->limit(20)
    ->get(['id', 'name', 'price', 'stock', 'image']);

// Product details with reviews
$product = DB::table('products:p')
    ->leftJoin('reviews:r', 'p.id', 'r.product_id')
    ->select([
        'p.*',
        'count(r.id):review_count',
        'avg(r.rating):avg_rating'
    ])
    ->where('p.id', 1)
    ->groupBy('p.id')
    ->first();
```

### Analytics Dashboard

```php
// Sales by month
$monthlySales = DB::table('orders')
    ->select([
        'DATE_FORMAT(created_at, "%Y-%m"):month',
        'count(*):order_count',
        'sum(total):revenue'
    ])
    ->where('status', 'completed')
    ->where('created_at', '>=', date('Y-01-01'))
    ->groupBy('month')
    ->orderBy('month', 'DESC')
    ->get();

// Top customers
$topCustomers = DB::table('orders:o')
    ->join('users:u', 'o.user_id', 'u.id')
    ->select([
        'u.id',
        'u.name',
        'count(o.id):order_count',
        'sum(o.total):lifetime_value'
    ])
    ->where('o.status', 'completed')
    ->groupBy('u.id')
    ->having('order_count', '>', 5)
    ->orderBy('lifetime_value', 'DESC')
    ->limit(10)
    ->get();
```

### Blog System

```php
// Published posts with author info
$posts = DB::table('posts:p')
    ->join('users:u', 'p.author_id', 'u.id')
    ->leftJoin('categories:c', 'p.category_id', 'c.id')
    ->select([
        'p.id', 'p.title', 'p.slug', 'p.excerpt',
        'p.published_at', 'u.name:author_name',
        'c.name:category_name'
    ])
    ->whereNotNull('p.published_at')
    ->where('p.status', 'published')
    ->orderBy('p.published_at', 'DESC')
    ->limit(10)
    ->get();

// Post with comments count
$post = DB::table('posts:p')
    ->leftJoin('comments:c', 'p.id', 'c.post_id')
    ->select(['p.*', 'count(c.id):comment_count'])
    ->where('p.slug', 'my-first-post')
    ->groupBy('p.id')
    ->first();
```

---

## Security Best Practices

### 1. Always Use Prepared Statements

BuildQL automatically uses prepared statements for all values. Never concatenate user input:

```php
// ✅ SAFE - Uses prepared statements
$email = $_POST['email'];
$user = DB::table('users')->where('email', $email)->first();

// ❌ NEVER DO THIS
$user = DB::raw("SELECT * FROM users WHERE email = '$email'"); // SQL Injection risk!

// ✅ SAFE - If you want to manually write query then replace actual value to placeholder (?)  
//           and attach value to second param of raw() method as an array Like
$user = DB::raw("SELECT * FROM users WHERE email = ?", [$email]); // No SQL Injection risk!
```

### 2. Validate Column Names

While BuildQL validates column names, always validate user-controlled column names:

```php
// ✅ SAFE - Whitelist allowed columns
$allowedSortColumns = ['name', 'created_at', 'price'];
$sortBy = $_GET['sort'] ?? 'created_at';

if (in_array($sortBy, $allowedSortColumns)) {
    $products = DB::table('products')->orderBy($sortBy)->get();
}
```

### 3. Sanitize User Input

Always validate and sanitize data before insertion:

```php
// ✅ SAFE - Validate before insert
$name = trim($_POST['name']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

if ($email && strlen($name) > 0) {
    DB::table('users')->insert([
        'name' => $name,
        'email' => $email
    ]);
}
```

### 4. Use Transactions for Critical Operations

For operations involving multiple tables, use transactions via raw queries:

```php
DB::raw("START TRANSACTION");

try {
    DB::table('orders')->insert([
        'user_id' => 1,
        'total' => 99.99
    ]);
    
    DB::table('inventory')->update(['stock' => 5])->where('product_id', 1);
    
    DB::raw("COMMIT");
} catch (BuilderException $e) {
    DB::raw("ROLLBACK");
    throw $e;
}
```

---

## Error Handling

BuildQL throws `BuilderException` for all database errors:

```php
use BuildQL\Database\Query\DB;
use BuildQL\Database\Query\Exception\BuilderException;

try {
    $users = DB::table('users')->where('age', '>', 18)->get();
} catch (BuilderException $e) {
    // Get detailed error message
    echo $e->getErrorMessage();
    // Output: "Query Execution Failed: Table 'database.users' doesn't exist - Check your code in app.php at line 25"
    
    // Log error
    error_log($e->getErrorMessage());
}
```

### Common Exceptions

**Connection Errors:**
```php
// Connection is not established right now.
// Connection already established. Connection will not be established more than once.
// Connection Failed Due to : Access denied for user
```

**Query Errors:**
```php
// Where method is not optional in update case
// Where method is not optional in delete case
// Invalid column name : user_id;DROP TABLE users
// Query Preparation Failed: You have an error in your SQL syntax
// Query Execution Failed: You have an error in your SQL syntax
```

**Validation Errors:**
```php
// $values parameter must be a non-empty array
// Select method must be contain a proper non-empty array of columns name
// Invalid join clause (INVALID_TYPE)
```

---

## Advanced Usage

### Complex Queries

```php
// Subquery-like behavior using multiple queries
$userIds = DB::table('orders')
    ->select(['user_id'])
    ->where('status', 'completed')
    ->where('created_at', '>', date('Y-m-d', strtotime('-30 days')))
    ->groupBy('user_id')
    ->having('count(*)', '>', 5)
    ->get();

$topBuyers = array_column($userIds, 'user_id');

$users = DB::table('users')
    ->whereIn('id', $topBuyers)
    ->orderBy('name')
    ->get();
```

### Dynamic Query Building

```php
$query = DB::table('products');

// Apply filters conditionally
if (isset($_GET['category'])) {
    $query->where('category_id', $_GET['category']);
}

if (isset($_GET['min_price'])) {
    $query->where('price', '>=', $_GET['min_price']);
}

if (isset($_GET['max_price'])) {
    $query->where('price', '<=', $_GET['max_price']);
}

if (isset($_GET['search'])) {
    $query->where('name', 'LIKE', '%' . $_GET['search'] . '%');
}

// Apply sorting
$sortBy = $_GET['sort'] ?? 'name';
$sortDir = $_GET['dir'] ?? 'ASC';
$query->orderBy($sortBy, $sortDir);

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 20;
$query->limit($perPage, ($page - 1) * $perPage);

$products = $query->get();
```

### Pagination Helper (Example Implementation)

## E.g; The following is an example helper function you can add to your project:

```php
function paginate($table, $perPage = 15, $page = 1, $conditions = []) {
    $query = DB::table($table);
    
    // Apply conditions
    foreach ($conditions as $column => $value) {
        if (is_array($value)) {
            $query->whereIn($column, $value);
        } else {
            $query->where($column, $value);
        }
    }
    
    // Get total count
    $total = $query->count();
    
    // Get paginated results
    $offset = ($page - 1) * $perPage;
    $data = $query->limit($perPage, $offset)->get();
    
    return [
        'data' => $data,
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => ceil($total / $perPage)
    ];
}

// Usage
$result = paginate('products', 20, 2, ['status' => 'active']);
```

### Query Logging (Example Implementation)

```php
// Create a query logger
function logQuery($query) {
    $sql = $query->toRawSql();
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents('query.log', "[$timestamp] $sql\n", FILE_APPEND);
}

// Use in your application
$query = DB::table('users')->where('status', 'active');
logQuery($query);
$users = $query->get();
```

---

## Performance Tips

### 1. Use Specific Columns

```php
// ❌ SLOW - Retrieves all columns
$users = DB::table('users')->get();

// ✅ FASTER - Only needed columns
$users = DB::table('users')->get(['id', 'name', 'email']);
```

### 2. Add Indexes

Ensure frequently queried columns have database indexes:

```sql
CREATE INDEX idx_status ON users(status);
CREATE INDEX idx_created_at ON orders(created_at);
CREATE INDEX idx_user_email ON users(email);
```

### 3. Limit Results

Always use `limit()` for large tables:

```php
// ✅ Good practice
$recentPosts = DB::table('posts')
    ->orderBy('created_at', 'DESC')
    ->limit(50)
    ->get();
```

### 4. Use `first()` Instead of `get()[0]`

```php
// ❌ Less efficient
$user = DB::table('users')->where('id', 1)->get()[0];

// ✅ More efficient (adds LIMIT 1)
$user = DB::table('users')->where('id', 1)->first();
```

### 5. Avoid N+1 Queries

```php
// ❌ BAD - N+1 queries
$users = DB::table('users')->get();
$profiles = [];
foreach ($users as $user) {
    $profile = DB::table('profiles')->where('user_id', $user['id'])->first();
    if ($profile){
        $profiles[] = $profile;
    }
    else{
        $profiles[] = null;
    }
    // Process...
}

// ✅ GOOD - Single query with JOIN
$usersWithProfiles = DB::table('users')
    ->leftJoin('profiles', 'users.id', 'profiles.user_id')
    ->select(['users.*', 'profiles.bio', 'profiles.avatar'])
    ->get();
```

---

## Testing

BuildQL includes comprehensive Pest PHP tests. Run tests:

```bash
./vendor/bin/pest
```

### Writing Tests

```php
use BuildQL\Database\Query\DB;
use BuildQL\Database\Query\Exception\BuilderException;

beforeEach(function() {
    DB::resetConnection(); // for security perpective
    DB::setConnection('localhost', 'root', '', 'test_db');
});

test('select query generates correct SQL', function() {
    $sql = DB::table('users')
        ->where('status', 'active')
        ->toRawSql();
    
    expect($sql)->toContain('SELECT * FROM `users` WHERE `status` = ?');
});

test('insert method works correctly', function() {
    $result = DB::table('users')->insert([
        'name' => 'Test User',
        'email' => 'test@example.com'
    ]);
    
    expect($result)->toBeTrue();
});

afterEach(function() {
    DB::resetConnection(); // after the end of the script, reset the database connection
});
```

---

## Migration from Other Query Builders

### From Laravel Eloquent

BuildQL syntax is very similar to Laravel:

```php
// Laravel
DB::table('users')->where('status', 'active')->get();

// BuildQL (same!)
DB::table('users')->where('status', 'active')->get();
```

**Key Differences:**
- No model system (use arrays instead)
- No automatic timestamps
- `find()` requires WHERE conditions in BuildQL (except `find(id)`)
- No `pluck()`, `chunk()`, or `cursor()` methods

### From PDO

```php
// PDO  (it's to long and messy)
$stmt = $pdo->prepare("SELECT * FROM users WHERE status = ?");
$stmt->execute(['active']);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// BuildQL (simpler and easy to use!)
$users = DB::table('users')->where('status', 'active')->get();
```

---

## Troubleshooting

### Issue: "Connection is not established"

**Solution:** Call `DB::setConnection()` or `DB::boot()` before using queries.

```php
DB::setConnection('localhost', 'root', '', 'mydb');
```

---

### Issue: "Connection already established"

**Solution:** You can only connect once. To reconnect:

```php
DB::resetConnection();
DB::setConnection('localhost', 'root', '', 'new_db');
```

---

### Issue: "Where method is not optional in update case"

**Solution:** `update()` and `delete()` require WHERE conditions:

```php
// ❌ Will throw exception
DB::table('users')->update(['status' => 'active']);

// ✅ Add WHERE condition
DB::table('users')->where('id', 1)->update(['status' => 'active']);
```

---

### Issue: "Invalid column name"

**Solution:** Column names can only contain letters, numbers, underscores, dots, hyphens, and colons (for aliases) and parenthetis () if are using aggregates function :

```php
// ❌ Invalid
DB::table('users')->where('user name', 'John');
DB::table('users')->where('`user_name`', 'John');
DB::table('posts')->where('count(`user_id`):user_post_count', 'John');

// ✅ Valid
DB::table('users')->where('user_name', 'John');
DB::table('posts')->where('count(user_id):user_post_count', 'John');
```

---

### Issue: Query returns empty array

**Debugging:**

```php
// 1. Check the generated SQL
$sql = DB::table('users')->where('status', 'active')->toRawSql();
echo $sql;

// 2. Test with raw SQL
$result = DB::raw("SELECT * FROM users WHERE status = ?", ['active']);
print_r($result);

// 3. Check database connection
$db = DB::raw("SELECT DATABASE() as db");
print_r($db);
```

---

## Limitations

1. **No ORM Features:** BuildQL is a query builder, not an ORM. No models or relationships.
2. **Single Database:** Only supports MySQL/MariaDB via MySQLi.
3. **No Transactions API:** Use `DB::raw()` for transaction control.
4. **No Query Caching:** Results are not cached automatically.
5. **Basic Aggregates:** Complex window functions not supported.
6. **AND/OR Precedence:** Be aware of SQL operator precedence in complex WHERE clauses.

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Write tests for new features
4. Ensure all tests pass: `./vendor/bin/pest`
5. Submit a pull request

---

## Changelog

### Version 1.0.0 (Initial Release)
- Fluent query builder interface
- Support for SELECT, INSERT, UPDATE, DELETE operations
- WHERE, JOIN, GROUP BY, HAVING, ORDER BY clauses
- Aggregate functions (COUNT, SUM, MIN, MAX, AVG)
- Prepared statements for security
- Comprehensive test coverage

---

## License

MIT License - see [LICENSE](LICENSE) file for details.

---

## Support

- **Documentation:** [GitHub Wiki](https://github.com/yourusername/buildql-query-builder/wiki)
- **Issues:** [GitHub Issues](https://github.com/yourusername/buildql-query-builder/issues)
- **Email:** umar.pwu786@gmail.com

---

## Credits

**Author:** Umar Ali

**Inspired by:** Laravel Query Builder

Built with ❤️ for the PHP community.

---

## Quick Reference Card

```php
// Connection
DB::setConnection('host', 'user', 'pass', 'db');
DB::boot(); // From .env

// Basic Queries
DB::table('users')->get();
DB::table('users')->first();
DB::table('users')->find(1);
DB::table('users')->count();
DB::table('users')->all();

// Filtering
->where('column', 'value')
->orWhere('column', 'value')
->whereIn('column', [1, 2, 3])
->whereNull('column')
->whereNotNull('column')

// Joins
->join('table', 'key1', 'key2')
->leftJoin('table', 'key1', 'key2')

// Sorting & Limiting
->orderBy('column', 'DESC')
->limit(10)
->offset(20)

// Grouping
->groupBy('column')
->having('count', '>', 5)

// Aggregates
->selectAggregate(count: '*:total')

// Modifications
->insert(['name' => 'John'])
->update(['status' => 'active'])
->delete()

// Utilities
->toRawSql()
->select(['col1', 'col2'])
->distinct()

// Raw Queries
DB::raw('SELECT * FROM users WHERE id = ?', [1])
```

---

**Happy Querying with BuildQL!**