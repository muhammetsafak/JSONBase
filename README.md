# JSONBase

It is a library that allows to use JSON files as database.

_**Note :** Do not use this library for big data. This library is suitable for use only for very small data._

## Requirements

- PHP 7.4 or higher
- PHP JSON Extension

## Installation

Include the JSonBase.php file and let it know in which directory to keep the JSon files.

```
composer require muhammetsafak/jsonbase
```

## Usage

```php
require_once 'src/Base.php';
```

```php
$jsonbase_dir = __DIR__ . '/data/';
$jsonbase = new \JSONBase\Base($jsonbase_dir);
```

### Table Create

For JsonBase, each table is actually a `.json` file.

```php
$jsonbase->create_table("table_name");
```

Definition of `create_table()` method:

```php
public function create_table(string $name): bool
```

### Insert

```php
$data = array("col_name" => "Value", "column" => "columnsValue");
$jsonbase->insert("table_name", $data);
```

Definition of `insert()` method

```php
public function insert(string $from, array $data): bool
```

#### Multi Insert

```php
$data = array(
    array("col_name" => "Value", "column" => "columnsValue"),
    array("col_name" => "Value2", "column" => "columnsValue2")
);
$jsonbase->multiInsert("table_name", $data);
```

Definition of `multiInsert()` method

```php
public function multiInsert(string $from, array $data): bool
```

This method may throw an error if $data is not a multi-array.

### Update

```php
$data = ["column" => "newValue", "col" => "colNewValue"];
$where = ["columnName" => "colValue"];
$jsonbase->update("table_name", $where, $data);
```

Definition of `update()` method

```php
public function update(string $from, array $where, array $data = []): bool
```

### Delete

```php
$where = ["id" => "340"];
$jsonbase->delete("table_name", $where);
```

Definition of `delete()` method

```php
public function delete(string $from, array $where): bool
```

### DROP

It tries to completely delete the table and its contents.

Definition of `drop()` method

```php
public function drop(string $from): bool
```

**Example**

```php
$jsonbase->drop("table_name");
```

### TRUNCATE

Empties all data in a table by deleting it.

```php
public function truncate(string $from): bool
```

**Example**

```php
$jsonbase->truncate("table_name");
```

### Rename

Changes the name of a table.

```php
public function rename(string $from, string $rename): bool
```

**Example**

```php
$jsonbase->rename("old_table_name", "new_table_name");
```

### Copy

Allows a table to be copied along with its data.

Caution: If there is a different table with the table name to be copied, it will overwrite (delete) it to avoid conflicts.

```php
public function copy(string $from, string $copyName): bool
```

**Example**

```php
$jsonbase->copy("table_name", "table_name_copy");
```

### Donwload

It allows you to download the JSonBase file. This method will try to initiate a download via the browser by adding a timestamp to the filename along with its contents.

```php
public function download(string $from): void
```

**Example**

```php
$jsonbase->download("table_name");
```

It stops the PHP script after it. It does nothing if the requested table does not exist.

### Tables

Returns the loaded table names as an array.

```php
public function tables(): array
```

**Example**

```php
foreach($jsonbase->tables() as $table_name){
    echo $table_name;
}
```

produces a similar output:

```
users
settings
posts
```

#### Size

Returns the total size of the table in bytes.

```php
public function size(string $from): int 
```

**Example**

```php
foreach($jsonbase->tables() as $table_name){
    echo $table_name . " : " . $jsonbase->size($table_name) . ' Byte';
}
```

produces a similar output:

```
users : 503 Byte
settings : 373 Byte
posts : 4392 Byte
```

#### rowSize

Returns the total number of rows in the table.

```php
public function rowSize(string $from): int
```

**Example**

```php
foreach($jsonbase->tables() as $table_name){
    echo $table_name . " : " . $jsonbase->rowSize($table_name) . ' rows';
}
```

produces a similar output:

```
users : 5 rows
settings : 12 rows
posts : 8 rows
```

### QUERY BUILD

You can use the `select()`, `from()` and `where()` methods to create a query in the JSonBase library.

**_`select()`_** It is used to generate a Json query. Specifies the columns in the results from the `row()` and `rows()` methods. `*` means all columns. If more than one column is desired, they must be separated by `,`.

Definition of `select()` method

```php
public function select(string $select = '*'): self
```

**_`from()`_** It is used to generate a Json query. Indicates on which Json the query to be executed with `get()` method will be executed.

Definition of `from()` method

```php
public function from(string $from): self
```

`$from` a table name.

**_`where()`_** It is used to generate a Json query. Creates a where array for the query to be executed with the `get()` method. Multiple matches can be made using this method chaining.

Definition of `where()` method

```php
public function where(string $key, string $value): self
```

`$key` a column name

`$value` value in column

Multiple where can be added using the `where()` method multiple times in a row or chaining.

The query constructed with the `from()` and `where()` methods should be executed with the `get()` method.

**_`get()`_** Execute the query created with the `select()`, `from()` and `where()` methods and get the results if any. You can access the results obtained with this method with the `row()`, `rows()` methods.

Definition of `get()` method

```php
public function get(): self
```

#### Results

You can access the results obtained by executing the `get()` method with the `row()`, `rows()` methods.

**_`row()`_** Returns the first result obtained as an associative array.

Definition of `row()` method

```php
public function row(): array|false
```

**_`rows()`_** Returns the resulting results as an associative array.

Definition of `rows()` method

```php
public function rows(): array|false
```

#### Num_Rows

**_`num_rows()`_** Returns the number of rows affected.

Definition of `num_rows()` method

```php
public function num_rows(): int
```

***

# Examples

Suppose we have a `students.json` file like the one below.

```json
[
    {"id":"885","name":"Muhammet","sex":"male","age":"18"},
    {"id":"956","name":"John","sex":"male","age":"18"},
    {"id":"957","name":"Jennifer","sex":"famale","age":"17"},
    {"id":"958","name":"Elizabeth","sex":"famale","age":"19"}
]
```

**Example 1 :**

```php
$jsonbase->select('name')->from('students')->where('id', '885')->get();
$size = $jsonbase->num_rows();
if($size > 0){
    $row = $jsonbase->row();
    echo $row['name'];
}else{
    echo 'Result not found!';
}
```

Output:

```
Muhammet
```

**Example 2 :**

```php
$jsonbase->select('id, name')->from('students')->where('sex', 'male')->get();
$size = $jsonbase->num_rows();
if($size > 0){
    foreach($jsonbase->rows() as $row){
        echo $row['id'] . " - " . $row['name'] . "\n";
    }
}else{
    echo 'Result not found!';
}
```

Output:

```
885 - Muhammet
956 - John
```

**Example 3 :**

```php
$jsonbase->select('id, name')->from('students')->where('sex', 'famale')->where('age', '17')->get();
$size = $jsonbase->num_rows();
if($size > 0){
    foreach($jsonbase->rows() as $row){
        echo $row['id'] . " - " . $row['name'] . "\n";
    }
}else{
    echo 'Result not found!';
}
```

Output:

```
957 - Jennifer
```

***

## Getting Help

If you have questions, concerns, bug reports, etc, please file an issue in this repository's Issue Tracker.

## Getting Involved

> All contributions to this project will be published under the MIT License. By submitting a pull request or filing a bug, issue, or feature request, you are agreeing to comply with this waiver of copyright interest.

There are two primary ways to help:

- Using the issue tracker, and
- Changing the code-base.

### Using the issue tracker

Use the issue tracker to suggest feature requests, report bugs, and ask questions. This is also a great way to connect with the developers of the project as well as others who are interested in this solution.

Use the issue tracker to find ways to contribute. Find a bug or a feature, mention in the issue that you will take on that effort, then follow the Changing the code-base guidance below.

### Changing the code-base

Generally speaking, you should fork this repository, make changes in your own fork, and then submit a pull request. All new code should have associated unit tests that validate implemented features and the presence or lack of defects. Additionally, the code should follow any stylistic and architectural guidelines prescribed by the project. In the absence of such guidelines, mimic the styles and patterns in the existing code-base.

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE)
