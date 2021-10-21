# Laracademy Generators

[![Latest Stable Version](https://poser.pugx.org/laracademy/generators/v/stable)](https://packagist.org/packages/laracademy/generators) [![Total Downloads](https://poser.pugx.org/laracademy/generators/downloads)](https://packagist.org/packages/laracademy/generators) [![Latest Unstable Version](https://poser.pugx.org/laracademy/generators/v/unstable)](https://packagist.org/packages/laracademy/generators) [![License](https://poser.pugx.org/laracademy/generators/license)](https://packagist.org/packages/laracademy/generators)

**Laracademy Generators** - is a tool set that helps speed up the development process of a Laravel application.

**Author(s):**
* [Laracademy](https://laracademy.co) ([@laracademy](http://twitter.com/laracademy), michael@laracademy.co)

## Requirements

1. PHP 7.4+
2. Laravel 6.*
3. MySQL *

** For Laravel 5.* please use the version 1.5

## Usage

### Step 1: Install through Composer

```
composer require "laracademy/generators" --dev
```

### Step 2: Artisan Command
Now that we have added the generator to our project the last thing to do is run Laravel's Arisan command

```
php artisan
```

You will see the following in the list

```
generate:modelfromtable
```

## Commands

### generate:modelfromtable

This command will read your database table and generate a model based on that table structure. The fillable fields, casts, dates and even namespacing will be filled in automatically.

You can use this command to generate a single table, multiple tables or all of your tables at once.

This command comes with a bunch of different options, please see below for each parameter

* --table=
  * This parameter if filled in will generate a model for the given table
  * You can also pass in a list of tables using comma separated values
  * When omitted, **all** tables will generate a model
    * In this scenario you can optionally specify a whitelist/blacklist in `config/modelfromtable.php`
    * `migrations` table will be blacklisted by default
* --connection=
  * By default, if omitted, the default connection found in `config/database.php` will be used
  * To specify a connection, first ensure that it exists in your `config/database.php`
* --folder=
  * By default, all models are store in your _app/_ directory. If you wish to store them in another place you can provide the relative path from your base laravel application.
  * Alternatively, use a lambda in `config/modelfromtable.php` to dynamically specify folder path
* --namespace=
  * By default, all models will have the namespace of `App\Models`
  * Alternatively, use a lambda in `config/modelfromtable.php` to dynamically specify namespace
* --debug=[_true|false (default)_]
  * Shows some more information while running
* --singular=[_true|false (default)_]
  * This will create a singular titled model, e.g. "Categories" -> "Category"
* --overwrite=[_true|false (default)_]
  * Overwrite model file(s) if exists
* --timestamps=[_true|false (default)_]
  * whether to timestamp or not

## Examples (CLI)

### Generating a single table

```
php artisan generate:modelfromtable --table=users
```

### Generating a multiple tables

```
php artisan generate:modelfromtable --table=users,posts
```

### Changing to another connection found in `database.php`

```
php artisan generate:modelfromtable --connection=spark
```

### Changing the folder where to /app/Models

```
php artisan generate:modelfromtable --table=user --folder=app\Models
```

## Configuration file for saving defaults, dynamic lambdas
A [config file](https://github.com/laracademy/generators/blob/master/config/modelfromtable.php) should be in your project's config folder (if not, you can easily create it). Through this, you can set defaults you commonly use to cut down on the input your command line call requires. Some fields, like `namespace`, accept a static value or, more powerfully, a lambda to generate dynamic values. Additional fields not available to the CLI are available in the config. See below.

### Whitelist/Blacklist (config only)
Particularly large databases often have a number of tables that aren't meant to have models. These can easily be filtered through either the whitelist or blacklist (or both!). Laravel's "migrations" table is already included in the blacklist. One nice feature is that you can wildcard table names if that makes sense for your situation...
```php
'blacklist' => ['migrations'];
'whitelist' => ['user_address', 'system_*'];
```

### Filename, using lambda
Large databases sometimes use a pattern of prefixing for organization, which you can use to organize your model files through a lambda.
```php
'filename' => fn(string $tableName) => Str::studly(Str::after($tableName, '_')),
```
In this example, 'system_user' would generate the filename 'User'.
_Note that this is also available through the CLI, but it probably doesn't make as much sense to set there._

### Folder path, using lambda
Using the last example, you can also organize the folder path using the prefix...
```php
'folder' => (function (string $tableName) {
    $prefix = Str::studly(Str::before($tableName, '_'));
    $path = app()->path('Models') . "/{$prefix}";

    if (!is_dir($path)) {
        mkdir($path);
    }

    return $path;
}),
```
In this example, 'system_user' would generate the folder path 'path/to/your/install/app/Models/System'

### Namespace, using lambda
Using the last example, you would want to then generate a matching namespace to the file path
```php
'namespace' => fn(string $folderpath) => 'App/Models' . Str::after($folderpath, app()->path('Models')),
```
Therefore the folder path 'path/to/your/install/app/Models/System' would generate the namespace 'App\Models\System'

### Delimiter (config only)
By default array values are delimited with a simple comma, but a common preference is to delimit with a newline as well.
```php
'delimiter' => ",\n" . str_repeat(' ', 8),
```
Result:
```php
class SomeModel extends Model
{
    protected $fillable = [
        'SomeField',
        'AnotherField',
        'YetAnotherField'
    ];
```

## License
ModelGen is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Bug Reporting and Feature Requests
Please add as many details as possible regarding submission of issues and feature requests

### Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
