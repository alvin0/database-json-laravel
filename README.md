Database Json Laravel -  php flat file database based on JSON files
=============

PHP Library to use JSON files like a database.   
Functionality inspired by ORM's

Requirements
-------
- PHP 5.4+
- Composer

Installation
-------

composer require alvin0/database-json-laravel

Structure of table files
-------

`table_name.data.json` - table file with data   
`table_name.config.json` - table file with configuration 

    
Basic Usage
------

Setup file .env patch :
```php
JSON_DATABASE_PATCH=../storage/database-json/
```

In your config/app.php add to the end of the aliases array:
```php
'DatabaseJson' => DatabaseJson\Classes\Database::class,
'RelationDatabaseJson' => DatabaseJson\Classes\Relation::class,
```

### Methods

##### Chain methods

- `limit()` - returns results between a certain number range. Should be used right before ending method `find_all()`.
- `orderBy()` - sort rows by key in order, can order by more than one field (just chain it). 
- `groupBy()` - group rows by field.
- `where()` - filter records. Alias: `and_where()`.
- `orWhere()` - other type of filtering results. 
- `with()` - join other tables by defined relations

##### Ending methods

- `addFields()` - append new fields into existing table
- `deleteFields()` - removing fields from existing table
- `save()` - insert or Update data.
- `delete()` - deleting data.
- `relations()` - returns array with table relations
- `config()` - returns object with configuration.
- `fields()` - returns array with fields name.
- `schema()` - returns assoc array with fields name and fields type `field => type`.
- `lastId()` - returns last ID from table.
- `find()` - returns one row with specified ID.
- `findOrFail()` - returns one row with specified ID when null show message error.
- `findAll()` - returns rows.
- `toArray()` - returns array rows. Should be used after ending method `find_all()`,`findOrFail()` or `find()`.
- `Pagination()` - returns rows with paginate.
- `toArrayWithPaginate()` - returns array rows. Should be used after ending method `Pagination()`.
- `asArray()` - returns data as indexed or assoc array: `['field_name' => 'field_name']`. Should be used after ending method `find_all()` or `find()`.
- `count()` - returns the number of rows. Should be used after ending method `find_all()`,`findOrFail()` or `find()`.

### Create database
```php
DatabaseJson::create('table_name', array(
    'id' => 'integer',
    'nickname' => 'string',
    {field_name} => {field_type}
));
```
More informations about field types and usage in PHPDoc
	
### Remove database
```php
DatabaseJson::remove('table_name');
```

### Check if a database exists
```php
try{
    \DatabaseJson\Classes\Helpers\Validate::table('table_name')->exists();
} catch(\DatabaseJson\Classes\DatabaseJsonException $e){
    //Database doesn't exist
}
```

### Select

#### Multiple select
```php
$table = DatabaseJson::table('table_name')->findAll();
    
foreach($table as $row)
{
    print_r($row);
}
```
#### Multiple select to array
```php
DatabaseJson::table('table_name')->findAll()->toArray();
```
#### Paginate
```php
DatabaseJson::table('table_name')->Pagination(5);
```
#### Paginate to array
```php
DatabaseJson::table('table_name')->Pagination(5)->toArrayWithPaginate();
```
#### Single record select
```php
DatabaseJson::table('table_name')->find(1);

echo $row->id;
```
Type ID of row in `find()` method.

You also can do something like that to get first matching record:
```php
DatabaseJson::table('table_name')->where('name', '=', 'John')->find();

echo $row->id;
```

### Insert
```php
$row = DatabaseJson::table('table_name');

$row->nickname = 'new_user';
$row->save();
```
Do not set the ID.

### Update

It's very smilar to `Inserting`.
```php
$row = DatabaseJson::table('table_name')->find(1); //Edit row with ID 1

$row->nickname = 'edited_user';

$row->save();
```
### Remove

#### Single record deleting
```php
DatabaseJson::table('table_name')->find(1)->delete(); //Will remove row with ID 1

// OR

DatabaseJson::table('table_name')->where('name', '=', 'John')->find()->delete(); //Will remove John from DB

```
#### Multiple records deleting
```php
DatabaseJson::table('table_name')->where('nickname', '=', 'edited_user')->delete();
```
#### Clear table
```php
DatabaseJson::table('table_name')->delete();
```
### Relations

To work with relations use class Relation
```php
use DatabaseJson\Classes\Relation as RelationDatabaseJson; // example
```

#### Relation types

- `belongsTo` - relation many to one
- `hasMany` - relation one to many
- `hasAndBelongsToMany` - relation many to many

#### Methods

##### Chain methods

- `belongsTo()` - set relation belongsTo
- `hasMany()` - set relation hasMany
- `hasAndBelongsToMany()` - set relation hasAndBelongsToMany
- `localKey()` - set relation local key
- `foreignKey()` - set relation foreign key
- `with()` - allow to work on existing relation

##### Ending methods

- `setRelation()` - creating specified relation
- `removeRelation()` - creating specified relation
- `getRelation()` - return informations about relation
- `getJunction()` - return name of junction table in `hasAndBelongsToMany` relation

#### Create relation
```php
RelationDatabaseJson::table('table1')->belongsTo('table2')->localKey('table2_id')->foreignKey('id')->setRelation();
RelationDatabaseJson::table('table2')->hasMany('table1')->localKey('id')->foreignKey('table2_id')->setRelation();
RelationDatabaseJson::table('table2')->hasAndBelongsToMany('table1')->localKey('id')->foreignKey('id')->setRelation(); // Junction table will be crete automaticly
```

#### Remove relation
```php
RelationDatabaseJson::table('table1')->with('table2')->removeRelation();
```
#### Get relation information
You can do it by two ways. Use Standard Database class or Relation but results will be different.
```php
RelationDatabaseJson::table('table1')->with('table2')->getRelation(); // relation with specified table
DatabaseJson::table('table1')->relations(); // all relations
DatabaseJson::table('table1')->relations('table2'); // relation with specified table
```

Description
------
For some examples please check `examples.md` and `tutorial.md` file.
More informations you can find in PHPDoc, I think it's documented very well.
This is a development project from the Lazer-Database project :link https://github.com/Greg0/Lazer-Database
