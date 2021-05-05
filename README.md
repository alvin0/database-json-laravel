
Database Json Laravel - php flat file database based on JSON files
Library to use JSON files like a database.
Functionality inspired by Eloquent

Requirements
---

- PHP 7.2.5+

- Composer

Installation
---
  
You can install the package via composer

    composer require alvin0/database-json-laravel

Optional: The service provider will automatically get registered. Or you may manually add the service provider in your  `config/app.php`  file:

    'providers' => [
	    // ...
	    DatabaseJson\DataBaseJsonServiceProvider::class,
    ];

You should publish  the  `config/databasejson.php`  [config file]([https://github.com/alvin0/DatabaseJsonLaravel/src/config/permission.php) .

Structure of table files
---

`table_name.data.json` - table file with data
`table_name.config.json` - table file with configuration


Basic Usage
---

###  I. Create Model and Migration with command line

#### 1. Model
##### Create model : 
    php artisan databasejson:model User -m

##### Optional :
	 -m  : Generate a migrate for the given model.
	 -f : Create the class even if the model already exists
 
##### Code generate

```php
namespace  App\DatabaseJson\Models;

use DatabaseJson\Model;  

class User extends  Model
{

}
```

#### 2. Migration
##### 2.1 Create a migration table

    php artisan databasejson:migration user --table=users 

##### Code generate 

```php
namespace  App\DatabaseJson\Migrations;

use DatabaseJson\DatabaseJson;
use DatabaseJson\Migration;

class CreateTableUserMigrateMigrate extends  Migration
{
	/**
	* How to create table
	*
	* DatabaseJson::table('NameTable',array(
	* {field_name} => {field_type} More information about field types and usage in PHPDoc
	* ));
	*/
	/**
	* Run the migrations.
	*
	* @return  void
	*/

	public  function  up()
	{
		DatabaseJson::create('users', array(
			'name' => 'string',
			'old' => 'integer',
			'created_at' => 'string',
			'updated_at' => 'string',
		));
	}

}
```

##### Optional

The --table option may also be used to indicate the name of the table.
the --update option will create migrate with method update table

##### 2.2 Update migrate table

The update only supports adding or removing columns in the table

If you want to delete the table, use this method in the function up() :

    DatabaseJson::remove('table_name');
##### example create migrate type update 
    php artisan databasejson:migration user --table="users" --update
##### Code generate 
    
```php
namespace  App\DatabaseJson\Migrations;

use DatabaseJson\DatabaseJson;
use DatabaseJson\Migration;

class CreateTableUserMigrateMigrate extends  Migration
{
	/**
	* Run the migrations.
	*
	* @return  void
	*/
	public  function  up()
	{
		DatabaseJson::table('users')->addFields([
			//'name' => 'string'
			//{field_name} => {field_type} More information about field types and usage in PHPDoc
		]);
		
		//DatabaseJson::table('users')->deleteFields([
			//'name',
			//{field_name}
		//]);
	}

}
```
##### 2.3 Run Migrate

    php artisan databasejson:migrate

##### Optional

--fresh : remove all table and up
--path : Specify a path

### II. Basic Usage

#### 1. Model Conventions

By default, model expects `created_at` and `updated_at` columns to exist on your tables. If you do not wish to have these columns automatically managed by Model, set the `$timestamps` property on your model to `false`:

```php
namespace  App\DatabaseJson\Models;

use DatabaseJson\Model;  

class User extends  Model
{
	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    
}
```

#### 2. Inserting & Updating & Delete Models

##### 2.1 Insert
To create a new record in the database, create a new model instance, set attributes on the model, then call the  `save` or use static function `create`  method:

```php
use App\DatabaseJson\Models\User;

$user = new User;
$user->name = 'alvin';
$user->old = 27;
$user->save();
```

Example
```php
use App\DatabaseJson\Models\User;

$user = User::create([
	'name' => 'alvin',
	'old' => 27
]);
```

##### 2.2 Update
To create a new record in the database, create a new model instance, set attributes on the model, then call the  `save` or use static function `create`  method:

```php
use App\DatabaseJson\Models\User;

$user = new User;
$user->id = 1;
$user->name = 'alvin';
$user->old = 27;
$user->save();
```

Example
```php
use App\DatabaseJson\Models\User;

$id = 1;
$user = User::update([
	'name' => 'alvin',
	'old' => 28
],$id);
```
##### 2.3 Delete

Remove data with constraints
```php
use App\DatabaseJson\Models\User;

$id = 1;
$user = User::where('name','alvin')->delete();
$userById = User::find(1)->delete();
```

Remove all data in table
```php
use App\DatabaseJson\Models\User;

$id = 1;
$user = User::delete();
```
#### 3. Retrieving Models

    all() -> This is a static function used to retrieve all objects in the model.
    find($id) -> This is a static function used to retrieve an object by id in the model.

Adding Additional Constraints

You may add constraints to queries, and then use the `get()` or `paginate($perpage)` method to retrieve the results

    where() - filter records ( Standard operators =, !=, >, <, >=, <=, like )
    orWhere() - other type of filtering results.
    orderBy() - sort rows by key in order, can order by more than one field (just chain it).
    groupBy() - group rows by field.

Example :

-  use `all()` retrieve the results

 ```php
use App\DatabaseJson\Models\User;

$users = User::all();
```

-  use `get()` retrieve the results
 ```php
use App\DatabaseJson\Models\User;

$users = User::where('name','alvin')
	->where('old', '>=', 18)
	->get();
```

-  use `paginate($perpage)` retrieve the results
 ```php
use App\DatabaseJson\Models\User;

$users = User::paginate(10);
```
- add constraints
 ```php
use App\DatabaseJson\Models\User;

$users = User::where('old', '>=', 18)->paginate(10);
```


#### 4 Relations

##### 4.1 setup relationship 

There are 2 relationships when applied : belongsTo and hasMany

local_key default is `id`

- belongsTo
foreign_key default is `primaryKey` table relation
```php
return $this->belongsTo('App\DatabaseJson\Models\User', 'local_key', 'foreign_key');
```

```php
namespace  App\DatabaseJson\Models;

use DatabaseJson\Model;  

class Blog extends  Model
{
	public  function  user()
	{
		$this->belongsTo(User::class);
	}
    
}
```

- hasMany
```php
return $this->hasMany('App\DatabaseJson\Models\Blog', 'foreign_key', 'local_key');
```

```php
namespace  App\DatabaseJson\Models;

use DatabaseJson\Model;  

class User extends  Model
{
	public  function  blogs()
	{
		return  $this->hasMany(Blog::class, 'user_id');
	}
    
}
```

##### 4.2 Retrieve relationship 

- belongsTo
```php
	//return model App\DatabaseJson\Models\User
	$userBlog = Blog::find(1)->user
```
- hasMany
```php
	//return Illuminate\Support\Collection
	$blogsByUser = User::find(1)->blogs
```
- Appends relational data to model results when retrieved with function `with()`
```php
	$users = User::with('blogs')->where('id', 1)->get();
 ```

#### 5 creating the accessor

##### 5.1Defining An Accessor

To define an accessor, create a getFooAttribute method on your model where Foo is the "studly" cased name of the column you wish to access. In this example, we'll define an accessor for the first_name attribute. The accessor will automatically be called by Eloquent when attempting to retrieve the value of the first_name attribute:
```php
<?php

namespace App;

namespace  App\DatabaseJson\Models;

class User extends Model
{
    /**
     * Get the user's first name.
     *
     * @param  string  $value
     * @return string
     */
    public function getFirstNameAttribute($value)
    {
        return ucfirst($value);
    }
}
 ```

As you can see, the original value of the column is passed to the accessor, allowing you to manipulate and return the value. To access the value of the accessor, you may access the first_name attribute on a model instance:

```php
$user = \App\DatabaseJson\Models\User::find(1);

$firstName = $user->first_name;

 ```

##### 5.2 Appending Values

After creating the accessor, add the attribute name to the appends property on the model. Note that attribute names are typically referenced in "snake case", even though the accessor is defined using "camel case":

```php
<?php

namespace App;

namespace  App\DatabaseJson\Models;

class User extends Model
{
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['first_name'];
}
 ```

### Description

More informations you can find in PHPDoc, I think it's documented very well.

This is a development project from the Lazer-Database project :link https://github.com/Greg0/Lazer-Database